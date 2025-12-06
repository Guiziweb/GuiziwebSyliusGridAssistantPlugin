<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Processor;

use Guiziweb\SyliusGridAssistantPlugin\Context\GridContext;
use Guiziweb\SyliusGridAssistantPlugin\Service\GridSchemaBuilder;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

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
            'Call the filter_grid tool ONCE with the appropriate criteria based on the user query. ' .
            'After the tool returns a redirect_url, respond with a short confirmation message. ' .
            'Do NOT call filter_grid again after it succeeds. ' .
            'If the user query is unclear, make your best guess based on available filters. ' .
            'If you cannot apply the requested filter (missing filter, invalid value, etc.), ' .
            'DO NOT call the tool. Instead, respond with a brief explanation of what went wrong ' .
            'and what filters are available. ' .
            'Today is %s.',
            (new \DateTimeImmutable())->format('Y-m-d')
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

        try {
            $result = $this->agent->call($messages, ['stream' => false]);
            $this->aiLogger->info('[GridAssistant] Agent response', [
                'content' => $result->getContent(),
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
            // Return the AI's response as feedback to the user
            $aiResponse = $result->getContent();
            if (is_string($aiResponse) && '' !== trim($aiResponse)) {
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