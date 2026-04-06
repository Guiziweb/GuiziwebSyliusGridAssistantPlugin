<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Filter\BooleanFilter;

final class BooleanFilterSchemaBuilder extends AbstractFilterSchemaBuilder
{
    public static function getType(): string
    {
        return 'boolean';
    }

    protected function buildSchema(Filter $filter): array
    {
        return [
            'type' => 'string',
            'enum' => [BooleanFilter::TRUE, BooleanFilter::FALSE],
            'description' => $this->translateLabel($filter->getLabel()),
        ];
    }
}
