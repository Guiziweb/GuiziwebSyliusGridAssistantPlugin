<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\EntityFilterSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EntityFilterSchemaBuilderTest extends TestCase
{
    private EntityFilterSchemaBuilder $builder;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->builder = new EntityFilterSchemaBuilder($translator);
    }

    private function filter(string $label = 'Customer', string $type = 'ux_autocomplete', array $formOptions = []): Filter
    {
        $filter = Filter::fromNameAndType('customer', $type);
        $filter->setLabel($label);
        $filter->setFormOptions($formOptions);

        return $filter;
    }

    public function testBuildSingleReturnsStringType(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('string', $schema['type']);
    }

    public function testBuildMultipleReturnsArrayType(): void
    {
        $filter = $this->filter('Customer', 'ux_autocomplete', ['multiple' => true]);

        $schema = $this->builder->build($filter);

        self::assertSame('array', $schema['type']);
        self::assertSame('string', $schema['items']['type']);
    }

    public function testBuildDescriptionContainsLabel(): void
    {
        $schema = $this->builder->build($this->filter('Customer'));

        self::assertStringContainsString('Customer', $schema['description']);
    }

    public function testBuildDescriptionUsesDefaultChoiceLabelName(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertStringContainsString('name', $schema['description']);
    }

    public function testBuildDescriptionUsesCustomChoiceLabel(): void
    {
        $filter = $this->filter('Customer', 'ux_autocomplete', [
            'extra_options' => ['choice_label' => 'email'],
        ]);

        $schema = $this->builder->build($filter);

        self::assertStringContainsString('email', $schema['description']);
    }
}