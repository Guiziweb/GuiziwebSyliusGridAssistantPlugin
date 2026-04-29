<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

use Sylius\Component\Grid\Definition\Filter;

final class BooleanFilterValueFormatter implements FilterValueFormatterInterface
{
    public static function getType(): string
    {
        return 'boolean';
    }

    public function format(mixed $value, Filter $filter): FilterFormatResult
    {
        if (is_bool($value)) {
            return new FilterFormatResult($value ? 'true' : 'false');
        }

        return new FilterFormatResult(null);
    }
}
