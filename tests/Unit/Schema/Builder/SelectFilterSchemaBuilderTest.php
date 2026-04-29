<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\SelectFilterSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SelectFilterSchemaBuilderTest extends TestCase
{
    private SelectFilterSchemaBuilder $builder;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->builder = new SelectFilterSchemaBuilder($translator);
    }

    private function filter(string $label = 'State', array $formOptions = []): Filter
    {
        $filter = Filter::fromNameAndType('state', 'select');
        $filter->setLabel($label);
        $filter->setFormOptions($formOptions);

        return $filter;
    }

    public function testBuildSingleReturnsStringTypeWithEnum(): void
    {
        $filter = $this->filter('State', ['choices' => ['New' => 'new', 'Fulfilled' => 'fulfilled']]);

        $schema = $this->builder->build($filter);

        self::assertSame('string', $schema['type']);
        self::assertSame(['new', 'fulfilled'], $schema['enum']);
    }

    public function testBuildMultipleReturnsArrayType(): void
    {
        $filter = $this->filter('State', [
            'choices' => ['New' => 'new', 'Fulfilled' => 'fulfilled'],
            'multiple' => true,
        ]);

        $schema = $this->builder->build($filter);

        self::assertSame('array', $schema['type']);
        self::assertSame(['new', 'fulfilled'], $schema['items']['enum']);
    }

    public function testBuildWithNoChoicesReturnsEmptyEnum(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame([], $schema['enum']);
    }

    public function testBuildDescriptionContainsLabel(): void
    {
        $schema = $this->builder->build($this->filter('State'));

        self::assertStringContainsString('State', $schema['description']);
    }

    public function testBuildMultipleDescriptionMentionsMultiple(): void
    {
        $filter = $this->filter('State', ['choices' => ['New' => 'new'], 'multiple' => true]);

        $schema = $this->builder->build($filter);

        self::assertStringContainsString('multiple', $schema['description']);
    }
}