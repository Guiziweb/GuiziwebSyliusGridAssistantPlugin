<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\FilterSchemaBuilderInterface;
use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\FilterSchemaBuilderRegistry;
use Guiziweb\SyliusGridAssistantPlugin\Schema\GridSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Field;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GridSchemaBuilderTest extends TestCase
{
    public function testFiltersWithAiSearchableFalseAreExcluded(): void
    {
        $grid = $this->makeGrid();
        $grid->addFilter($this->makeFilter('customer', 'string', ['ai_searchable' => true]));
        $grid->addFilter($this->makeFilter('internal_notes', 'string', ['ai_searchable' => false]));
        $grid->addFilter($this->makeFilter('date', 'date'));

        $schema = $this->makeBuilder($grid)->buildSchema('any');

        self::assertArrayHasKey('customer', $schema['filters']);
        self::assertArrayHasKey('date', $schema['filters']);
        self::assertArrayNotHasKey('internal_notes', $schema['filters']);
    }

    public function testSortableFieldsWithAiSearchableFalseAreExcluded(): void
    {
        $grid = $this->makeGrid();
        $grid->addField($this->makeField('number', sortable: 'number'));
        $grid->addField($this->makeField('internal_id', sortable: 'internalId', options: ['ai_searchable' => false]));
        $grid->addField($this->makeField('total', sortable: 'total', options: ['ai_searchable' => true]));
        $grid->addField($this->makeField('actions', sortable: null));

        $schema = $this->makeBuilder($grid)->buildSchema('any');

        self::assertArrayHasKey('number', $schema['sortable_fields']);
        self::assertArrayHasKey('total', $schema['sortable_fields']);
        self::assertArrayNotHasKey('internal_id', $schema['sortable_fields']);
        self::assertArrayNotHasKey('actions', $schema['sortable_fields']);
    }

    private function makeGrid(): Grid
    {
        return Grid::fromCodeAndDriverConfiguration('test_grid', 'doctrine/orm', ['class' => 'Foo']);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function makeFilter(string $name, string $type, array $options = []): Filter
    {
        $filter = Filter::fromNameAndType($name, $type);
        $filter->setOptions($options);

        return $filter;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function makeField(string $name, ?string $sortable, array $options = []): Field
    {
        $field = Field::fromNameAndType($name, 'string');
        $field->setSortable($sortable);
        $field->setLabel($name);
        $field->setOptions($options);

        return $field;
    }

    private function makeBuilder(Grid $grid): GridSchemaBuilder
    {
        $gridProvider = $this->createMock(GridProviderInterface::class);
        $gridProvider->method('get')->willReturn($grid);

        $registry = new FilterSchemaBuilderRegistry();
        $registry->register(new class implements FilterSchemaBuilderInterface {
            public static function getType(): string|array
            {
                return ['string', 'date'];
            }

            public function build(Filter $filter): array
            {
                return ['type' => 'string'];
            }
        });

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new GridSchemaBuilder($gridProvider, $registry, $translator);
    }
}