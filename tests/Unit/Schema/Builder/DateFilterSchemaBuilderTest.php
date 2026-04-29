<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\DateFilterSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DateFilterSchemaBuilderTest extends TestCase
{
    private DateFilterSchemaBuilder $builder;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->builder = new DateFilterSchemaBuilder($translator);
    }

    private function filter(string $label = 'Created at'): Filter
    {
        $filter = Filter::fromNameAndType('createdAt', 'date');
        $filter->setLabel($label);

        return $filter;
    }

    public function testBuildReturnsObjectType(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('object', $schema['type']);
    }

    public function testBuildHasStartAndEndProperties(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertArrayHasKey('start', $schema['properties']);
        self::assertArrayHasKey('end', $schema['properties']);
    }

    public function testBuildPropertiesAreNullableStringWithDateFormat(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('string', $schema['properties']['start']['anyOf'][0]['type']);
        self::assertSame('date', $schema['properties']['start']['anyOf'][0]['format']);
        self::assertSame('null', $schema['properties']['start']['anyOf'][1]['type']);
        self::assertSame('string', $schema['properties']['end']['anyOf'][0]['type']);
        self::assertSame('date', $schema['properties']['end']['anyOf'][0]['format']);
        self::assertSame('null', $schema['properties']['end']['anyOf'][1]['type']);
    }

    public function testBuildPropertiesAreRequired(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertContains('start', $schema['required']);
        self::assertContains('end', $schema['required']);
    }

    public function testBuildDescriptionMentionsDateFormat(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertStringContainsString('YYYY-MM-DD', $schema['description']);
    }
}