<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

interface GridSchemaBuilderInterface
{
    /**
     * Build a complete schema for a grid.
     *
     * @return array{
     *     grid_code: string,
     *     entity_class: string|null,
     *     filters: array<string, array<string, mixed>>,
     *     sortable_fields: array<string, array{label: string|bool|null, path: string|null}>,
     *     default_sorting: array<string, string>
     * }
     */
    public function buildSchema(string $gridCode): array;

    public function gridExists(string $gridCode): bool;
}
