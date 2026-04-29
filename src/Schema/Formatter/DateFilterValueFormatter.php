<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Filter\DateFilter;

final class DateFilterValueFormatter implements FilterValueFormatterInterface
{
    public static function getType(): string
    {
        return DateFilter::NAME;
    }

    public function format(mixed $value, Filter $filter): FilterFormatResult
    {
        if (!is_array($value)) {
            return new FilterFormatResult(null);
        }

        $result = [];

        if (is_string($value['start'] ?? null) && '' !== $value['start']) {
            $result['from'] = ['date' => $value['start']];
        }

        if (is_string($value['end'] ?? null) && '' !== $value['end']) {
            $result['to'] = ['date' => $value['end']];
        }

        return new FilterFormatResult(!empty($result) ? $result : null);
    }
}
