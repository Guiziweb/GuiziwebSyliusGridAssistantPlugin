<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Processor;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\FilterValueFormatterRegistry;
use Guiziweb\SyliusGridAssistantPlugin\Schema\GridSchemaBuilder;
use Guiziweb\SyliusGridAssistantPlugin\Toolbox\GridToolSchemaFactory;
use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\ObjectResult;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GridQueryProcessor
{
    /**
     * @param non-empty-string $model
     */
    public function __construct(
        private PlatformInterface $platform,
        private string $model,
        private GridSchemaBuilder $schemaBuilder,
        private GridToolSchemaFactory $schemaFactory,
        private FilterValueFormatterRegistry $formatterRegistry,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $aiLogger,
        private RateLimiterFactoryInterface $aiQueryLimiter,
        private Security $security,
        private TranslatorInterface $translator,
        #[Autowire(service: 'sylius.grid.chain_provider')]
        private GridProviderInterface $gridProvider,
    ) {
    }

    /**
     * Process a natural language query and return a redirect URL with filters.
     *
     * @param array<string, mixed> $routeParams
     *
     * @return array{redirect_url: string}|array{error: string}
     */
    public function process(string $query, string $gridCode, string $routeName, array $routeParams): array
    {
        $user = $this->security->getUser();
        if (null === $user) {
            return ['error' => $this->translator->trans('guiziweb.grid_assistant.rate_limit_unauthenticated')];
        }

        $limiter = $this->aiQueryLimiter->create($user->getUserIdentifier());
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();

            return ['error' => $this->translator->trans('guiziweb.grid_assistant.rate_limit_exceeded', ['%seconds%' => max(1, $retryAfter)])];
        }

        if (!$this->schemaBuilder->gridExists($gridCode)) {
            return ['error' => sprintf('Grid "%s" not found.', $gridCode)];
        }

        $gridSchema = $this->schemaBuilder->buildSchema($gridCode);
        $parametersSchema = $this->schemaFactory->buildParameters($gridSchema);

        $systemPrompt = sprintf(
            "You are a Sylius grid filtering assistant.\n\n" .
            "Rules:\n" .
            "- criteria: all filter fields are always present. Set to null any filter not explicitly mentioned by the user.\n" .
            "- sorting: all sorting fields are always present. Set to null any field the user did not explicitly ask to sort by.\n" .
            "- message: write a short natural language summary of what you applied, or explain why no filter could be applied if the query is too vague.\n" .
            '- Today is %s.',
            (new \DateTimeImmutable())->format('Y-m-d'),
        );

        $messages = new MessageBag(
            Message::forSystem($systemPrompt),
            Message::ofUser(sprintf('User request: "%s"', $query)),
        );

        $responseFormat = [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'grid_filter',
                'schema' => $parametersSchema,
                'strict' => true,
            ],
        ];

        $this->aiLogger->info('[GridAssistant] Processing query', [
            'query' => $query,
            'gridCode' => $gridCode,
            'routeName' => $routeName,
        ]);

        try {
            $deferred = $this->platform->invoke($this->model, $messages, ['response_format' => $responseFormat]);
            $result = $deferred->getResult();

            if ($result instanceof ObjectResult) {
                /** @var array{criteria?: array<string, mixed>, sorting?: array<string, mixed>, message?: string|null}|null $data */
                $data = is_array($result->getContent()) ? $result->getContent() : null;
            } elseif ($result instanceof TextResult) {
                /** @var array{criteria?: array<string, mixed>, sorting?: array<string, mixed>, message?: string|null}|null $data */
                $data = json_decode($result->getContent(), true);
            } else {
                $data = null;
            }

            $this->aiLogger->info('[GridAssistant] LLM response', ['data' => $data]);
        } catch (\Throwable $e) {
            $this->aiLogger->error('[GridAssistant] Platform error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['error' => sprintf('AI processing failed: %s', $e->getMessage())];
        }

        if (!is_array($data)) {
            return ['error' => 'Could not parse AI response. Please try rephrasing.'];
        }

        $criteria = is_array($data['criteria'] ?? null) ? $data['criteria'] : [];
        $sorting = is_array($data['sorting'] ?? null) ? $data['sorting'] : [];
        $message = is_string($data['message'] ?? null) ? $data['message'] : null;

        try {
            $grid = $this->gridProvider->get($gridCode);
        } catch (\InvalidArgumentException) {
            return ['error' => sprintf('Grid "%s" not found.', $gridCode)];
        }

        $validCriteria = $this->validateAndFormatCriteria($criteria, $grid);
        $validSorting = $this->validateSorting($sorting, $grid);

        $this->aiLogger->info('[GridAssistant] Applying filters', [
            'validCriteria' => $validCriteria,
            'validSorting' => $validSorting,
        ]);

        if (empty($validCriteria) && empty($validSorting)) {
            return ['error' => $message ?? 'Could not determine any filter from your query. Please try rephrasing.'];
        }

        $urlParams = $routeParams;
        if (!empty($validCriteria)) {
            $urlParams['criteria'] = $validCriteria;
        }
        if (!empty($validSorting)) {
            $urlParams['sorting'] = $validSorting;
        }

        try {
            $url = $this->urlGenerator->generate($routeName, $urlParams);
        } catch (\Exception $e) {
            return ['error' => sprintf('Failed to generate URL: %s', $e->getMessage())];
        }

        return ['redirect_url' => $url];
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<string, mixed>
     */
    private function validateAndFormatCriteria(array $criteria, Grid $grid): array
    {
        $validCriteria = [];

        foreach ($criteria as $filterName => $value) {
            if (null === $value) {
                continue;
            }

            if (!$grid->hasFilter($filterName)) {
                $this->aiLogger->warning('[GridAssistant] Unknown filter skipped', ['filter' => $filterName]);

                continue;
            }

            $filter = $grid->getFilter($filterName);
            $filterType = $filter->getType();

            if ($this->formatterRegistry->has($filterType)) {
                $result = $this->formatterRegistry->get($filterType)->format($value, $filter);
                $formatted = $result->value;
            } else {
                $formatted = $value;
            }

            if (null !== $formatted) {
                $validCriteria[$filterName] = $formatted;
            }
        }

        return $validCriteria;
    }

    /**
     * @param array<string, mixed> $sorting
     *
     * @return array<string, string>
     */
    private function validateSorting(array $sorting, Grid $grid): array
    {
        $validSorting = [];
        $sortableFields = [];

        foreach ($grid->getFields() as $field) {
            if ($field->isSortable()) {
                $sortableFields[] = $field->getName();
            }
        }

        foreach ($sorting as $field => $direction) {
            if (null === $direction) {
                continue;
            }

            if (!in_array($field, $sortableFields, true)) {
                $this->aiLogger->warning('[GridAssistant] Unknown sortable field skipped', ['field' => $field]);

                continue;
            }

            if (!is_string($direction)) {
                continue;
            }

            $normalizedDirection = strtolower(trim($direction));
            if (!in_array($normalizedDirection, ['asc', 'desc'], true)) {
                $normalizedDirection = 'asc';
            }

            $validSorting[$field] = $normalizedDirection;
        }

        return $validSorting;
    }
}
