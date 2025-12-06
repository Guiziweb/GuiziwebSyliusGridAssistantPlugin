<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Service;

use Guiziweb\SyliusGridAssistantPlugin\Schema\FilterSchemaBuilderRegistry;
use Guiziweb\SyliusGridAssistantPlugin\Schema\TranslateLabelTrait;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds a JSON schema describing all available filters and sortable fields for a grid.
 * This schema is used by the AI to understand what filtering options are available.
 */
final readonly class GridSchemaBuilder
{
    use TranslateLabelTrait;

    public function __construct(
        #[Autowire(service: 'sylius.grid.chain_provider')]
        private GridProviderInterface $gridProvider,
        private FilterSchemaBuilderRegistry $filterSchemaBuilderRegistry,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Build a complete schema for a grid.
     *
     * @return array{
     *     grid_code: string,
     *     entity_class: string|null,
     *     filters: array<string, array<string, mixed>>,
     *     sortable_fields: array<string, array{label: string|bool|null, path: string|null}>,
     *     default_sorting: array<string, string>
     * }
     */
    public function buildSchema(string $gridCode): array
    {
        $grid = $this->gridProvider->get($gridCode);

        return [
            'grid_code' => $gridCode,
            'entity_class' => $this->extractEntityClass($grid),
            'filters' => $this->buildFiltersSchema($grid),
            'sortable_fields' => $this->buildSortableFieldsSchema($grid),
            'default_sorting' => $grid->getSorting(),
        ];
    }

    /**
     * Build schema for all filters.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildFiltersSchema(Grid $grid): array
    {
        $filters = [];

        foreach ($grid->getEnabledFilters() as $name => $filter) {
            $filters[$name] = $this->buildFilterSchema($filter);
        }

        return $filters;
    }

    /**
     * Build schema for a single filter.
     *
     * @return array<string, mixed>
     */
    private function buildFilterSchema(Filter $filter): array
    {
        $type = $filter->getType();

        if (!$this->filterSchemaBuilderRegistry->has($type)) {
            return [];
        }

        return $this->filterSchemaBuilderRegistry->get($type)->build($filter);
    }

    /**
     * Build schema for sortable fields.
     *
     * @return array<string, array{label: string, path: string|null}>
     */
    private function buildSortableFieldsSchema(Grid $grid): array
    {
        $sortableFields = [];

        foreach ($grid->getEnabledFields() as $name => $field) {
            if ($field->isSortable()) {
                $sortableFields[$name] = [
                    'label' => $this->translateLabel($field->getLabel()),
                    'path' => $field->getSortable(),
                ];
            }
        }

        return $sortableFields;
    }

    /**
     * Extract entity class from grid driver configuration.
     */
    private function extractEntityClass(Grid $grid): ?string
    {
        $driverConfig = $grid->getDriverConfiguration();

        return $driverConfig['class'] ?? null;
    }

    /**
     * Check if a grid exists.
     */
    public function gridExists(string $gridCode): bool
    {
        try {
            $this->gridProvider->get($gridCode);

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}