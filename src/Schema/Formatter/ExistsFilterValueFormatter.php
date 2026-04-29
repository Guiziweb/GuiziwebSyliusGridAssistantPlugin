<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

use Sylius\Component\Grid\Definition\Filter;

final class ExistsFilterValueFormatter implements FilterValueFormatterInterface
{
    public static function getType(): string
    {
        return 'exists';
    }

    public function format(mixed $value, Filter $filter): FilterFormatResult
    {
        if (is_bool($value)) {
            return new FilterFormatResult($value);
        }

        return new FilterFormatResult(null);
    }
}
