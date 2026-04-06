<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

final class FilterSchemaBuilderRegistry
{
    /** @var array<string, FilterSchemaBuilderInterface> */
    private array $builders = [];

    public function register(FilterSchemaBuilderInterface $builder): void
    {
        $types = $builder::getType();

        if (is_string($types)) {
            $types = [$types];
        }

        foreach ($types as $type) {
            $this->builders[$type] = $builder;
        }
    }

    public function has(string $type): bool
    {
        return isset($this->builders[$type]);
    }

    public function get(string $type): FilterSchemaBuilderInterface
    {
        if (!$this->has($type)) {
            throw new \InvalidArgumentException(sprintf('No schema builder registered for filter type "%s".', $type));
        }

        return $this->builders[$type];
    }
}
