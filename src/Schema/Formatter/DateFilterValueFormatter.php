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

        foreach (['start' => 'from', 'end' => 'to'] as $key => $bound) {
            $date = $value[$key] ?? null;
            if (is_string($date) && $this->isRealDate($date)) {
                $result[$bound] = ['date' => $date];
            }
        }

        return new FilterFormatResult(!empty($result) ? $result : null);
    }

    private function isRealDate(string $date): bool
    {
        if (1 !== preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[T ].*)?$/', $date, $m)) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}
