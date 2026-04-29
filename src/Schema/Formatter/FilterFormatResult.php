<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

final class FilterFormatResult
{
    /**
     * @param string[] $warnings
     */
    public function __construct(
        public readonly mixed $value,
        public readonly array $warnings = [],
    ) {
    }
}
