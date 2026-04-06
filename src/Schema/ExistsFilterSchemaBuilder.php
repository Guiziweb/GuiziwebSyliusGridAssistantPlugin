<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Filter;

final class ExistsFilterSchemaBuilder extends AbstractFilterSchemaBuilder
{
    public static function getType(): string
    {
        return 'exists';
    }

    protected function buildSchema(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());

        return [
            'type' => 'string',
            'enum' => ['true', 'false'],
            'description' => sprintf('%s - true: exists/not null, false: is null', $label),
        ];
    }
}
