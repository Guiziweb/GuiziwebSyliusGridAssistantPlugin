<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\ExistsFilterSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ExistsFilterSchemaBuilderTest extends TestCase
{
    private ExistsFilterSchemaBuilder $builder;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->builder = new ExistsFilterSchemaBuilder($translator);
    }

    private function filter(string $label = 'Has stock'): Filter
    {
        $filter = Filter::fromNameAndType('hasStock', 'exists');
        $filter->setLabel($label);

        return $filter;
    }

    public function testGetType(): void
    {
        self::assertSame('exists', ExistsFilterSchemaBuilder::getType());
    }

    public function testBuildReturnsBooleanType(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('boolean', $schema['type']);
    }

    public function testBuildDescriptionContainsLabel(): void
    {
        $schema = $this->builder->build($this->filter('Has stock'));

        self::assertStringContainsString('Has stock', $schema['description']);
    }

    public function testBuildDescriptionExplainsSemantics(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertStringContainsString('true', $schema['description']);
        self::assertStringContainsString('false', $schema['description']);
    }
}