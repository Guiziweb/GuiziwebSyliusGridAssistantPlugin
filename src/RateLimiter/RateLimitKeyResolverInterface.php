<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\RateLimiter;

interface RateLimitKeyResolverInterface
{
    /**
     * Returns the key used to identify the bucket consumed by the AI query rate limiter.
     *
     * Implementations may throw a Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessorException
     * if no key can legitimately be produced (e.g. anonymous user when authentication is required).
     */
    public function resolve(): string;
}
