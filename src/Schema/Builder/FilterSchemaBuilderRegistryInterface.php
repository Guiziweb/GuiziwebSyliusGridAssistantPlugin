<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

interface FilterSchemaBuilderRegistryInterface
{
    public function register(FilterSchemaBuilderInterface $builder): void;

    public function has(string $type): bool;

    public function get(string $type): FilterSchemaBuilderInterface;
}
