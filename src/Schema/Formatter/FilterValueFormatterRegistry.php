<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

final class FilterValueFormatterRegistry
{
    /** @var array<string, FilterValueFormatterInterface> */
    private array $formatters = [];

    public function register(FilterValueFormatterInterface $formatter): void
    {
        $types = $formatter::getType();

        if (is_string($types)) {
            $types = [$types];
        }

        foreach ($types as $type) {
            $this->formatters[$type] = $formatter;
        }
    }

    public function has(string $type): bool
    {
        return isset($this->formatters[$type]);
    }

    public function get(string $type): FilterValueFormatterInterface
    {
        if (!$this->has($type)) {
            throw new \InvalidArgumentException(sprintf('No value formatter registered for filter type "%s".', $type));
        }

        return $this->formatters[$type];
    }
}
