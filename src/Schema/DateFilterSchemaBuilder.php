<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Filter\DateFilter;

final class DateFilterSchemaBuilder extends AbstractFilterSchemaBuilder
{
    public static function getType(): string
    {
        return DateFilter::NAME;
    }

    protected function buildSchema(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());
        $today = (new \DateTimeImmutable())->format('Y-m-d');

        return [
            'type' => 'object',
            'properties' => [
                'start' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Start date (YYYY-MM-DD)',
                ],
                'end' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'End date (YYYY-MM-DD)',
                ],
            ],
            'additionalProperties' => false,
            'description' => sprintf(
                '%s - Today is %s. Convert relative dates (this week, last month, etc.) to YYYY-MM-DD',
                $label,
                $today,
            ),
        ];
    }
}
