<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Toolbox;

use Guiziweb\SyliusGridAssistantPlugin\Toolbox\GridToolSchemaFactory;
use PHPUnit\Framework\TestCase;

final class GridToolSchemaFactoryTest extends TestCase
{
    private GridToolSchemaFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new GridToolSchemaFactory();
    }

    private function gridSchema(array $filters = [], array $sortableFields = []): array
    {
        return [
            'filters' => $filters,
            'sortable_fields' => $sortableFields,
        ];
    }

    public function testBuildParametersReturnsObjectType(): void
    {
        $schema = $this->factory->buildParameters($this->gridSchema());

        self::assertSame('object', $schema['type']);
    }

    public function testBuildParametersHasCriteriaAndSorting(): void
    {
        $schema = $this->factory->buildParameters($this->gridSchema());

        self::assertArrayHasKey('criteria', $schema['properties']);
        self::assertArrayHasKey('sorting', $schema['properties']);
    }

    public function testBuildParametersCriteriaContainsFilterSchemas(): void
    {
        $schema = $this->factory->buildParameters($this->gridSchema(
            filters: ['enabled' => ['type' => 'boolean', 'description' => 'Enabled']],
        ));

        $enabledProperty = $schema['properties']['criteria']['properties']['enabled'];
        self::assertArrayHasKey('anyOf', $enabledProperty);
        self::assertSame('boolean', $enabledProperty['anyOf'][0]['type']);
        self::assertSame('null', $enabledProperty['anyOf'][1]['type']);
    }

    public function testBuildParametersCriteriaFiltersAreRequired(): void
    {
        $schema = $this->factory->buildParameters($this->gridSchema(
            filters: ['enabled' => ['type' => 'boolean', 'description' => 'Enabled']],
        ));

        self::assertContains('enabled', $schema['properties']['criteria']['required']);
    }

    public function testBuildParametersSortingContainsSortableFields(): void
    {
        $schema = $this->factory->buildParameters($this->gridSchema(
            sortableFields: ['createdAt' => ['label' => 'Created at']],
        ));

        $createdAtProperty = $schema['properties']['sorting']['properties']['createdAt'];
        self::assertArrayHasKey('anyOf', $createdAtProperty);
        self::assertSame(['asc', 'desc'], $createdAtProperty['anyOf'][0]['enum']);
        self::assertSame('null', $createdAtProperty['anyOf'][1]['type']);
    }

    public function testBuildParametersSortingFieldsAreRequired(): void
    {
        $schema = $this->factory->buildParameters($this->gridSchema(
            sortableFields: ['createdAt' => ['label' => 'Created at']],
        ));

        self::assertContains('createdAt', $schema['properties']['sorting']['required']);
    }
}