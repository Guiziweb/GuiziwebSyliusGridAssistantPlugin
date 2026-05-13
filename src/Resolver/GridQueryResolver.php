<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Resolver;

use Guiziweb\SyliusGridAssistantPlugin\Schema\GridSchemaBuilderInterface;
use Guiziweb\SyliusGridAssistantPlugin\Toolbox\GridToolSchemaFactoryInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\ObjectResult;
use Symfony\AI\Platform\Result\TextResult;

final readonly class GridQueryResolver implements GridQueryResolverInterface
{
    /**
     * @param non-empty-string $model
     */
    public function __construct(
        private ?PlatformInterface $platform,
        private string $model,
        private GridSchemaBuilderInterface $schemaBuilder,
        private GridToolSchemaFactoryInterface $schemaFactory,
        private LoggerInterface $aiLogger,
        private ClockInterface $clock,
    ) {
    }

    public function resolve(string $query, string $gridCode): ResolvedGridQuery
    {
        if (null === $this->platform) {
            throw new GridQueryResolverException('No AI platform configured. Install a bridge: composer require symfony/ai-openai-platform (or symfony/ai-gemini-platform, symfony/ai-anthropic-platform, symfony/ai-mistral-platform) and configure ai.platform.<provider> in your config.');
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
            $this->clock->now()->format('Y-m-d'),
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
        ]);

        try {
            $result = $this->platform->invoke($this->model, $messages, ['response_format' => $responseFormat])->getResult();
        } catch (\Throwable $e) {
            $this->aiLogger->error('[GridAssistant] Platform error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new GridQueryResolverException(sprintf('AI processing failed: %s', $e->getMessage()), 0, $e);
        }

        $data = match (true) {
            $result instanceof ObjectResult => is_array($result->getContent()) ? $result->getContent() : null,
            $result instanceof TextResult => json_decode($result->getContent(), true),
            default => null,
        };

        $this->aiLogger->info('[GridAssistant] LLM response', ['data' => $data]);

        if (!is_array($data)) {
            throw new GridQueryResolverException('Could not parse AI response. Please try rephrasing.');
        }

        return new ResolvedGridQuery(
            criteria: is_array($data['criteria'] ?? null) ? $data['criteria'] : [],
            sorting: is_array($data['sorting'] ?? null) ? $data['sorting'] : [],
            message: is_string($data['message'] ?? null) ? $data['message'] : null,
        );
    }
}
