<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Filter\StringFilter;

final class StringFilterSchemaBuilder extends AbstractFilterSchemaBuilder
{
    private const OPERATORS = [
        StringFilter::TYPE_EQUAL,
        StringFilter::TYPE_NOT_EQUAL,
        StringFilter::TYPE_CONTAINS,
        StringFilter::TYPE_NOT_CONTAINS,
        StringFilter::TYPE_STARTS_WITH,
        StringFilter::TYPE_ENDS_WITH,
        StringFilter::TYPE_EMPTY,
        StringFilter::TYPE_NOT_EMPTY,
        StringFilter::TYPE_IN,
        StringFilter::TYPE_NOT_IN,
    ];

    public static function getType(): string
    {
        return StringFilter::NAME;
    }

    protected function buildSchema(Filter $filter): array
    {
        $options = $filter->getOptions();
        $label = $this->translateLabel($filter->getLabel());
        $defaultOperator = is_string($options['type'] ?? null) ? $options['type'] : StringFilter::TYPE_CONTAINS;

        return [
            'type' => 'object',
            'properties' => [
                'value' => [
                    'type' => 'string',
                    'description' => 'The search value',
                ],
                'type' => [
                    'type' => 'string',
                    'enum' => self::OPERATORS,
                    'description' => sprintf('The comparison operator (default: %s)', $defaultOperator),
                ],
            ],
            'additionalProperties' => false,
            'description' => sprintf('%s - use {value: "text"} or {value: "text", type: "equal"}', $label),
        ];
    }
}
