<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Resolver;

interface GridQueryResolverInterface
{
    /**
     * Resolve a natural-language query against a grid schema into a structured query (criteria + sorting + message).
     *
     * @throws GridQueryResolverException if the resolution fails or the response cannot be parsed
     */
    public function resolve(string $query, string $gridCode): ResolvedGridQuery;
}
