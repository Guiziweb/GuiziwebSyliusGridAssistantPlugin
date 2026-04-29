<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\StringFilterSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Filter\StringFilter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class StringFilterSchemaBuilderTest extends TestCase
{
    private StringFilterSchemaBuilder $builder;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->builder = new StringFilterSchemaBuilder($translator);
    }

    private function filter(string $label = 'Search', array $options = []): Filter
    {
        $filter = Filter::fromNameAndType('search', 'string');
        $filter->setLabel($label);
        $filter->setOptions($options);

        return $filter;
    }

    public function testGetType(): void
    {
        self::assertSame(StringFilter::NAME, StringFilterSchemaBuilder::getType());
    }

    public function testBuildReturnsObjectType(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('object', $schema['type']);
    }

    public function testBuildHasValueAndTypeProperties(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertArrayHasKey('value', $schema['properties']);
        self::assertArrayHasKey('type', $schema['properties']);
    }

    public function testBuildTypePropertyHasOperatorEnum(): void
    {
        $schema = $this->builder->build($this->filter());

        $typeEnum = $schema['properties']['type']['anyOf'][0]['enum'];
        self::assertContains('contains', $typeEnum);
        self::assertContains('equal', $typeEnum);
        self::assertContains('in', $typeEnum);
        self::assertContains('empty', $typeEnum);
    }

    public function testBuildTypePropertyIsNullable(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('null', $schema['properties']['type']['anyOf'][1]['type']);
    }

    public function testBuildDefaultOperatorIsContains(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertStringContainsString('contains', $schema['properties']['type']['description']);
    }

    public function testBuildUsesFilterConfiguredDefaultOperator(): void
    {
        $schema = $this->builder->build($this->filter('Search', ['type' => 'equal']));

        self::assertStringContainsString('equal', $schema['properties']['type']['description']);
    }

    public function testBuildWithFixedTypeReturnsSimpleString(): void
    {
        $filter = Filter::fromNameAndType('number', 'string');
        $filter->setLabel('Number');
        $filter->setFormOptions(['type' => 'contains']);

        $schema = $this->builder->build($filter);

        self::assertSame('string', $schema['type']);
        self::assertArrayNotHasKey('properties', $schema);
    }
}