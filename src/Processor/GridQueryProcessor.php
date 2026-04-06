<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Processor;

use Guiziweb\SyliusGridAssistantPlugin\Context\GridContext;
use Guiziweb\SyliusGridAssistantPlugin\Service\GridSchemaBuilder;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\TextResult;

final readonly class GridQueryProcessor
{
    public function __construct(
        private AgentInterface $agent,
        private GridSchemaBuilder $schemaBuilder,
        private GridContext $gridContext,
        private LoggerInterface $aiLogger,
    ) {
    }

    /**
     * Process a natural language query and return a redirect URL with filters.
     *
     * @return array{redirect_url: string, warnings?: array<string>}|array{error: string}
     */
    public function process(string $query, string $gridCode, string $routeName, array $routeParams): array
    {
        // Verify grid exists
        if (!$this->schemaBuilder->gridExists($gridCode)) {
            return ['error' => sprintf('Grid "%s" not found.', $gridCode)];
        }

        // Set the grid context - this is used by FilterGridToolEnricher
        $this->gridContext->setContext($gridCode, $routeName, $routeParams);

        // Create message bag with system prompt and user query
        $systemPrompt = sprintf(
            'You are a grid filtering assistant. ' .
            'When the user query contains enough information to apply at least one filter, call the filter_grid tool ONCE. ' .
            'Only include filters explicitly mentioned by the user. Do not add filters that were not requested. ' .
            'For sorting: only include a sorting field if the user explicitly asked to sort by it. If the user did not mention sorting, pass an empty object {} for sorting. ' .
            'If the query is completely unclear and no filter can be applied at all, ' .
            'respond with a brief explanation of what filters are available. ' .
            'Today is %s.',
            (new \DateTimeImmutable())->format('Y-m-d'),
        );

        $messages = new MessageBag(
            Message::forSystem($systemPrompt),
            Message::ofUser(sprintf('Filter the grid: "%s"', $query)),
        );

        $this->aiLogger->info('[GridAssistant] Processing query', [
            'query' => $query,
            'gridCode' => $gridCode,
            'routeName' => $routeName,
        ]);

        $aiResponse = null;

        try {
            $result = $this->agent->call($messages, ['stream' => false]);
            if ($result instanceof TextResult) {
                $text = $result->getContent();
                $aiResponse = '' !== trim($text) ? $text : null;
            }

            $this->aiLogger->info('[GridAssistant] Agent response', [
                'content' => $aiResponse,
            ]);
        } catch (\Throwable $e) {
            $this->aiLogger->error('[GridAssistant] Agent error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['error' => sprintf('AI processing failed: %s', $e->getMessage())];
        } finally {
            // Get result before clearing context
            $toolResult = $this->gridContext->getResult();

            // Clean up context
            $this->gridContext->clear();
        }

        // Get redirect URL from context (set by FilterGridTool)
        $redirectUrl = $toolResult['redirect_url'] ?? null;

        $this->aiLogger->info('[GridAssistant] Redirect URL', [
            'redirectUrl' => $redirectUrl,
            'toolResult' => $toolResult,
        ]);

        if (null === $redirectUrl) {
            if (isset($toolResult['error'])) {
                return ['error' => $toolResult['error']];
            }

            if (null !== $aiResponse) {
                return ['error' => $aiResponse];
            }

            return ['error' => 'Could not generate filter criteria from your query. Please try rephrasing.'];
        }

        $response = ['redirect_url' => $redirectUrl];

        if (isset($toolResult['warnings']) && is_array($toolResult['warnings'])) {
            $response['warnings'] = $toolResult['warnings'];
        }

        return $response;
    }
}
