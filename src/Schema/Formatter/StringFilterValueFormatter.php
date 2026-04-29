<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Filter\StringFilter;

final class StringFilterValueFormatter implements FilterValueFormatterInterface
{
    public static function getType(): string
    {
        return StringFilter::NAME;
    }

    public function format(mixed $value, Filter $filter): FilterFormatResult
    {
        $formOptions = $filter->getFormOptions();
        $fixedType = is_string($formOptions['type'] ?? null) ? (string) $formOptions['type'] : null;

        $options = $filter->getOptions();
        $defaultOperator = is_string($options['type'] ?? null) ? (string) $options['type'] : 'contains';

        if (is_array($value)) {
            $type = is_string($value['type'] ?? null) ? (string) $value['type'] : $defaultOperator;
            $val = is_scalar($value['value'] ?? null) ? (string) $value['value'] : '';
        } else {
            $type = $defaultOperator;
            $val = is_scalar($value) ? (string) $value : '';
        }

        if ('' === $val && !in_array($type, ['empty', 'not_empty'], true)) {
            return new FilterFormatResult(null);
        }

        if (null !== $fixedType) {
            return new FilterFormatResult(['value' => $val]);
        }

        return new FilterFormatResult(['type' => $type, 'value' => $val]);
    }
}
