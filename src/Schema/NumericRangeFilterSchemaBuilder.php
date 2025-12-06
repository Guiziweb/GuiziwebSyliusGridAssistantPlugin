<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Filter;

final class NumericRangeFilterSchemaBuilder extends AbstractFilterSchemaBuilder
{
    public static function getType(): string
    {
        return 'numeric_range';
    }

    protected function buildSchema(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());

        return [
            'type' => 'object',
            'properties' => [
                'greaterThan' => [
                    'type' => 'number',
                    'description' => 'Minimum value',
                ],
                'lessThan' => [
                    'type' => 'number',
                    'description' => 'Maximum value',
                ],
            ],
            'additionalProperties' => false,
            'description' => $label,
        ];
    }
}