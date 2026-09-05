<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Field;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Definition\Grid;

final class AiExposure
{
    public const OPTION = 'ai_searchable';

    /**
     * @return array<string, Filter>
     */
    public static function filters(Grid $grid): array
    {
        $filters = [];

        foreach ($grid->getEnabledFilters() as $name => $filter) {
            if (!self::isOptedOut($filter->getOptions())) {
                $filters[$name] = $filter;
            }
        }

        return $filters;
    }

    /**
     * @return array<string, Field>
     */
    public static function sortableFields(Grid $grid): array
    {
        $fields = [];

        foreach ($grid->getEnabledFields() as $name => $field) {
            if ($field->isSortable() && !self::isOptedOut($field->getOptions())) {
                $fields[$name] = $field;
            }
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function isOptedOut(array $options): bool
    {
        return false === ($options[self::OPTION] ?? true);
    }
}
