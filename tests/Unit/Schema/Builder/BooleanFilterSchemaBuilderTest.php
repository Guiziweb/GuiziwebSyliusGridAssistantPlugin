<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\BooleanFilterSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BooleanFilterSchemaBuilderTest extends TestCase
{
    private BooleanFilterSchemaBuilder $builder;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->builder = new BooleanFilterSchemaBuilder($translator);
    }

    private function filter(string $label = 'Enabled'): Filter
    {
        $filter = Filter::fromNameAndType('enabled', 'boolean');
        $filter->setLabel($label);

        return $filter;
    }

    public function testGetType(): void
    {
        self::assertSame('boolean', BooleanFilterSchemaBuilder::getType());
    }

    public function testBuildReturnsBooleanType(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('boolean', $schema['type']);
    }

    public function testBuildIncludesTranslatedLabel(): void
    {
        $schema = $this->builder->build($this->filter('Enabled'));

        self::assertStringContainsString('Enabled', $schema['description']);
    }
}