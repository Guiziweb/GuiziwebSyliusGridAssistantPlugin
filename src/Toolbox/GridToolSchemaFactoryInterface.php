<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Toolbox;

interface GridToolSchemaFactoryInterface
{
    /**
     * @param array<string, mixed> $gridSchema
     *
     * @return array{type: 'object', properties: array<string, array<string, mixed>>, required: array<int, string>, additionalProperties: false}
     */
    public function buildParameters(array $gridSchema): array;
}
