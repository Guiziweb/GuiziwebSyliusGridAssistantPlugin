<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Context;

/**
 * Holds the current grid context for the AI request.
 *
 * This is a singleton service that stores grid information needed by the tool enricher.
 * The context must be cleared after each request via clear() to prevent state leakage.
 */
final class GridContext
{
    private ?string $gridCode = null;

    private ?string $routeName = null;

    private array $routeParams = [];

    private ?array $result = null;

    public function setContext(string $gridCode, string $routeName, array $routeParams = []): void
    {
        $this->gridCode = $gridCode;
        $this->routeName = $routeName;
        $this->routeParams = $routeParams;
    }

    public function getGridCode(): ?string
    {
        return $this->gridCode;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function hasContext(): bool
    {
        return null !== $this->gridCode;
    }

    public function clear(): void
    {
        $this->gridCode = null;
        $this->routeName = null;
        $this->routeParams = [];
        $this->result = null;
    }

    /**
     * @param array{redirect_url?: string, error?: string} $result
     */
    public function setResult(array $result): void
    {
        $this->result = $result;
    }

    /**
     * @return array{redirect_url?: string, error?: string}|null
     */
    public function getResult(): ?array
    {
        return $this->result;
    }
}
