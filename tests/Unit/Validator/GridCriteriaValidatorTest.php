<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Validator;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\FilterFormatResult;
use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\FilterValueFormatterInterface;
use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\FilterValueFormatterRegistryInterface;
use Guiziweb\SyliusGridAssistantPlugin\Validator\GridCriteriaValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Definition\Grid;

final class GridCriteriaValidatorTest extends TestCase
{
    public function testSkipsNullValues(): void
    {
        $grid = $this->makeGrid();
        $grid->addFilter(Filter::fromNameAndType('state', 'select'));

        $validator = new GridCriteriaValidator($this->emptyRegistry(), $this->createMock(LoggerInterface::class));

        self::assertSame([], $validator->validate(['state' => null], $grid));
    }

    public function testSkipsUnknownFilterAndLogsWarning(): void
    {
        $grid = $this->makeGrid();
        $grid->addFilter(Filter::fromNameAndType('state', 'select'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with('[GridAssistant] Unknown filter skipped', ['filter' => 'unknown']);

        $validator = new GridCriteriaValidator($this->emptyRegistry(), $logger);

        self::assertSame([], $validator->validate(['unknown' => 'value'], $grid));
    }

    public function testPassesValueThroughWhenNoFormatterRegistered(): void
    {
        $grid = $this->makeGrid();
        $grid->addFilter(Filter::fromNameAndType('state', 'select'));

        $validator = new GridCriteriaValidator($this->emptyRegistry(), $this->createMock(LoggerInterface::class));

        self::assertSame(['state' => 'new'], $validator->validate(['state' => 'new'], $grid));
    }

    public function testAppliesFormatterWhenRegistered(): void
    {
        $grid = $this->makeGrid();
        $grid->addFilter(Filter::fromNameAndType('state', 'select'));

        $formatter = $this->createMock(FilterValueFormatterInterface::class);
        $formatter->method('format')->willReturn(new FilterFormatResult('formatted'));

        $registry = $this->createMock(FilterValueFormatterRegistryInterface::class);
        $registry->method('has')->with('select')->willReturn(true);
        $registry->method('get')->with('select')->willReturn($formatter);

        $validator = new GridCriteriaValidator($registry, $this->createMock(LoggerInterface::class));

        self::assertSame(['state' => 'formatted'], $validator->validate(['state' => 'raw'], $grid));
    }

    public function testSkipsEntryWhenFormatterReturnsNull(): void
    {
        $grid = $this->makeGrid();
        $grid->addFilter(Filter::fromNameAndType('state', 'select'));

        $formatter = $this->createMock(FilterValueFormatterInterface::class);
        $formatter->method('format')->willReturn(new FilterFormatResult(null));

        $registry = $this->createMock(FilterValueFormatterRegistryInterface::class);
        $registry->method('has')->willReturn(true);
        $registry->method('get')->willReturn($formatter);

        $validator = new GridCriteriaValidator($registry, $this->createMock(LoggerInterface::class));

        self::assertSame([], $validator->validate(['state' => 'raw'], $grid));
    }

    public function testReturnsEmptyArrayForEmptyInput(): void
    {
        $validator = new GridCriteriaValidator($this->emptyRegistry(), $this->createMock(LoggerInterface::class));

        self::assertSame([], $validator->validate([], $this->makeGrid()));
    }

    private function makeGrid(): Grid
    {
        return Grid::fromCodeAndDriverConfiguration('test_grid', 'doctrine/orm', []);
    }

    private function emptyRegistry(): FilterValueFormatterRegistryInterface
    {
        $registry = $this->createMock(FilterValueFormatterRegistryInterface::class);
        $registry->method('has')->willReturn(false);

        return $registry;
    }
}
