<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Processor;

interface GridQueryProcessorInterface
{
    /**
     * Process a natural language query and return a redirect URL with filters applied.
     *
     * @param array<string, mixed> $routeParams
     *
     * @throws GridQueryProcessorException if the query cannot be processed (rate-limited, no filter determined, missing grid, etc.)
     */
    public function process(string $query, string $gridCode, string $routeName, array $routeParams): string;
}
