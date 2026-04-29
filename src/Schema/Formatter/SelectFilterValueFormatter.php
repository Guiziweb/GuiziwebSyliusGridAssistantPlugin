<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

use Sylius\Component\Grid\Definition\Filter;

final class SelectFilterValueFormatter implements FilterValueFormatterInterface
{
    public static function getType(): string|array
    {
        return ['select', 'enum'];
    }

    public function format(mixed $value, Filter $filter): FilterFormatResult
    {
        $formOptions = $filter->getFormOptions();
        /** @var array<mixed> $choicesRaw */
        $choicesRaw = is_array($formOptions['choices'] ?? null) ? $formOptions['choices'] : [];
        $choices = array_values($choicesRaw);
        $isMultiple = (bool) ($formOptions['multiple'] ?? false);

        if ($isMultiple && is_array($value)) {
            $filtered = array_values(array_filter($value, fn ($v) => in_array($v, $choices, true)));

            return new FilterFormatResult($filtered ?: null);
        }

        if (is_string($value) && in_array($value, $choices, true)) {
            return new FilterFormatResult($value);
        }

        if (empty($choices)) {
            return new FilterFormatResult($value);
        }

        return new FilterFormatResult(null);
    }
}
