<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

use Sylius\Component\Grid\Definition\Filter;

final class NumericRangeFilterValueFormatter implements FilterValueFormatterInterface
{
    public static function getType(): string
    {
        return 'numeric_range';
    }

    public function format(mixed $value, Filter $filter): FilterFormatResult
    {
        if (!is_array($value)) {
            return new FilterFormatResult(null);
        }

        $result = [];

        if (isset($value['greaterThan']) && is_numeric($value['greaterThan'])) {
            $result['greaterThan'] = (string) $value['greaterThan'];
        }

        if (isset($value['lessThan']) && is_numeric($value['lessThan'])) {
            $result['lessThan'] = (string) $value['lessThan'];
        }

        return new FilterFormatResult(!empty($result) ? $result : null);
    }
}
