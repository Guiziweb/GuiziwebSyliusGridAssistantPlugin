<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Resolver;

use Guiziweb\SyliusGridAssistantPlugin\Resolver\GridQueryResolver;
use Guiziweb\SyliusGridAssistantPlugin\Resolver\GridQueryResolverException;
use Guiziweb\SyliusGridAssistantPlugin\Schema\GridSchemaBuilderInterface;
use Guiziweb\SyliusGridAssistantPlugin\Toolbox\GridToolSchemaFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

final class GridQueryResolverTest extends TestCase
{
    public function testThrowsWhenNoPlatformIsConfigured(): void
    {
        $resolver = new GridQueryResolver(
            null,
            'gpt-4o',
            $this->createMock(GridSchemaBuilderInterface::class),
            $this->createMock(GridToolSchemaFactoryInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ClockInterface::class),
        );

        $this->expectException(GridQueryResolverException::class);
        $this->expectExceptionMessageMatches('/No AI platform configured/');

        $resolver->resolve('any query', 'sylius_admin_order');
    }
}