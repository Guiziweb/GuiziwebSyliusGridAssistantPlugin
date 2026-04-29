<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

use Sylius\Component\Grid\Definition\Filter;

final class MoneyFilterValueFormatter implements FilterValueFormatterInterface
{
    public static function getType(): string
    {
        return 'money';
    }

    public function format(mixed $value, Filter $filter): FilterFormatResult
    {
        if (!is_array($value)) {
            return new FilterFormatResult(null);
        }

        $result = [];

        if (isset($value['greaterThan']) && is_numeric($value['greaterThan'])) {
            $result['greaterThan'] = (float) $value['greaterThan'];
        }

        if (isset($value['lessThan']) && is_numeric($value['lessThan'])) {
            $result['lessThan'] = (float) $value['lessThan'];
        }

        if (isset($value['currency']) && is_string($value['currency'])) {
            $result['currency'] = strtoupper(trim($value['currency']));
        }

        return new FilterFormatResult(!empty($result) ? $result : null);
    }
}
