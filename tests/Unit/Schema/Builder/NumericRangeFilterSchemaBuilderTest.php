<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\NumericRangeFilterSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NumericRangeFilterSchemaBuilderTest extends TestCase
{
    private NumericRangeFilterSchemaBuilder $builder;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->builder = new NumericRangeFilterSchemaBuilder($translator);
    }

    private function filter(string $label = 'Price'): Filter
    {
        $filter = Filter::fromNameAndType('price', 'numeric_range');
        $filter->setLabel($label);

        return $filter;
    }

    public function testBuildReturnsObjectType(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('object', $schema['type']);
    }

    public function testBuildHasGreaterThanAndLessThanProperties(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertArrayHasKey('greaterThan', $schema['properties']);
        self::assertArrayHasKey('lessThan', $schema['properties']);
    }

    public function testBuildPropertiesAreNullableNumberType(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('number', $schema['properties']['greaterThan']['anyOf'][0]['type']);
        self::assertSame('null', $schema['properties']['greaterThan']['anyOf'][1]['type']);
        self::assertSame('number', $schema['properties']['lessThan']['anyOf'][0]['type']);
        self::assertSame('null', $schema['properties']['lessThan']['anyOf'][1]['type']);
    }

    public function testBuildPropertiesAreRequired(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertContains('greaterThan', $schema['required']);
        self::assertContains('lessThan', $schema['required']);
    }

    public function testBuildDisallowsAdditionalProperties(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertFalse($schema['additionalProperties']);
    }
}