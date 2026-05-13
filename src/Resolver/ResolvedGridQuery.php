<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Resolver;

final readonly class ResolvedGridQuery
{
    /**
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $sorting
     */
    public function __construct(
        public array $criteria,
        public array $sorting,
        public ?string $message,
    ) {
    }
}
