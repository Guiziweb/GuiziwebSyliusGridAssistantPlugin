<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

use Sylius\Component\Grid\Definition\Filter;

interface FilterValueFormatterInterface
{
    /**
     * @return string|string[]
     */
    public static function getType(): string|array;

    /**
     * Format a raw value received from the AI into the format expected by Sylius.
     * Returns null as 'value' if the criterion should be ignored.
     */
    public function format(mixed $value, Filter $filter): FilterFormatResult;
}
