<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

interface FilterValueFormatterRegistryInterface
{
    public function register(FilterValueFormatterInterface $formatter): void;

    public function has(string $type): bool;

    public function get(string $type): FilterValueFormatterInterface;
}
