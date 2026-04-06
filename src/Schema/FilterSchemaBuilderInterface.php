<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Filter;

interface FilterSchemaBuilderInterface
{
    /**
     * @return string|string[]
     */
    public static function getType(): string|array;

    /**
     * Build the JSON Schema for a filter.
     *
     * @return array<string, mixed>
     */
    public function build(Filter $filter): array;
}
