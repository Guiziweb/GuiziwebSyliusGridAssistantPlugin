<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Processor;

use Guiziweb\SyliusGridAssistantPlugin\Resolver\GridQueryResolverException;
use Guiziweb\SyliusGridAssistantPlugin\Resolver\GridQueryResolverInterface;
use Guiziweb\SyliusGridAssistantPlugin\Schema\GridSchemaBuilderInterface;
use Guiziweb\SyliusGridAssistantPlugin\Validator\GridCriteriaValidatorInterface;
use Guiziweb\SyliusGridAssistantPlugin\Validator\GridSortingValidatorInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GridQueryProcessor implements GridQueryProcessorInterface
{
    public function __construct(
        private GridQueryResolverInterface $queryResolver,
        private GridCriteriaValidatorInterface $criteriaValidator,
        private GridSortingValidatorInterface $sortingValidator,
        private GridSchemaBuilderInterface $schemaBuilder,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $aiLogger,
        private RateLimiterFactoryInterface $aiQueryLimiter,
        private Security $security,
        private TranslatorInterface $translator,
        #[Autowire(service: 'sylius.grid.chain_provider')]
        private GridProviderInterface $gridProvider,
    ) {
    }

    public function process(string $query, string $gridCode, string $routeName, array $routeParams): string
    {
        $user = $this->security->getUser();
        if (null === $user) {
            throw new GridQueryProcessorException($this->translator->trans('guiziweb.grid_assistant.rate_limit_unauthenticated'));
        }

        $limit = $this->aiQueryLimiter->create($user->getUserIdentifier())->consume();
        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();

            throw new GridQueryProcessorException($this->translator->trans('guiziweb.grid_assistant.rate_limit_exceeded', ['%seconds%' => max(1, $retryAfter)]));
        }

        if (!$this->schemaBuilder->gridExists($gridCode)) {
            throw new GridQueryProcessorException($this->translator->trans('guiziweb.grid_assistant.grid_not_found', ['%grid_code%' => $gridCode]));
        }

        try {
            $resolved = $this->queryResolver->resolve($query, $gridCode);
        } catch (GridQueryResolverException $e) {
            $this->aiLogger->warning('[GridAssistant] Resolver error', ['error' => $e->getMessage()]);

            throw new GridQueryProcessorException($this->translator->trans('guiziweb.grid_assistant.ai_processing_failed'), 0, $e);
        }

        $grid = $this->gridProvider->get($gridCode);

        $validCriteria = $this->criteriaValidator->validate($resolved->criteria, $grid);
        $validSorting = $this->sortingValidator->validate($resolved->sorting, $grid);

        $this->aiLogger->info('[GridAssistant] Applying filters', [
            'validCriteria' => $validCriteria,
            'validSorting' => $validSorting,
        ]);

        if (empty($validCriteria) && empty($validSorting)) {
            throw new GridQueryProcessorException($resolved->message ?? $this->translator->trans('guiziweb.grid_assistant.no_filter_determined'));
        }

        $urlParams = $routeParams;
        if (!empty($validCriteria)) {
            $urlParams['criteria'] = $validCriteria;
        }
        if (!empty($validSorting)) {
            $urlParams['sorting'] = $validSorting;
        }

        try {
            return $this->urlGenerator->generate($routeName, $urlParams);
        } catch (\Exception $e) {
            $this->aiLogger->warning('[GridAssistant] URL generation failed', ['error' => $e->getMessage()]);

            throw new GridQueryProcessorException($this->translator->trans('guiziweb.grid_assistant.url_generation_failed'), 0, $e);
        }
    }
}
