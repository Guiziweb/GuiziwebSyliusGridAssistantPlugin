<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Toolbox;

use Guiziweb\SyliusGridAssistantPlugin\Context\GridContext;
use Guiziweb\SyliusGridAssistantPlugin\Service\GridSchemaBuilder;
use Guiziweb\SyliusGridAssistantPlugin\Tool\FilterGridTool;
use Symfony\AI\Platform\Tool\Tool;

/**
 * Enriches the FilterGridTool with a dynamic JSON Schema based on the current grid.
 * This ensures the AI receives a strongly typed schema for the criteria parameter.
 */
final readonly class FilterGridToolEnricher implements ToolDescriptionEnricherInterface
{
    public function __construct(
        private GridContext $gridContext,
        private GridSchemaBuilder $schemaBuilder,
    ) {
    }

    public function supports(string $toolReference): bool
    {
        return $toolReference === FilterGridTool::class;
    }

    public function enrich(Tool $tool): Tool
    {
        if (!$this->gridContext->hasContext()) {
            return $tool;
        }

        $gridCode = $this->gridContext->getGridCode();
        $gridSchema = $this->schemaBuilder->buildSchema($gridCode);

        return new Tool(
            $tool->getReference(),
            $tool->getName(),
            $this->buildDescription($gridSchema),
            $this->buildParametersSchema($gridSchema),
        );
    }

    /**
     * Build the JSON Schema for tool parameters.
     *
     * @param array<string, mixed> $gridSchema
     *
     * @return array<string, mixed>
     */
    private function buildParametersSchema(array $gridSchema): array
    {
        /** @var array<string, array<string, mixed>> $filters */
        $filters = $gridSchema['filters'];
        /** @var array<string, array<string, mixed>> $sortableFields */
        $sortableFields = $gridSchema['sortable_fields'];

        $criteriaProperties = [];
        foreach ($filters as $filterName => $filterSchema) {
            $criteriaProperties[$filterName] = [
                'anyOf' => [$filterSchema, ['type' => 'null']],
                'description' => ($filterSchema['description'] ?? $filterName) . '. Set to null if the user did not mention this filter.',
            ];
        }

        $sortingProperties = [];
        foreach ($sortableFields as $fieldName => $fieldConfig) {
            $sortingProperties[$fieldName] = [
                'anyOf' => [
                    ['type' => 'string', 'enum' => ['asc', 'desc']],
                    ['type' => 'null'],
                ],
                'description' => sprintf('Sort by %s. Set to null if the user did not ask to sort by this field.', $fieldConfig['label'] ?? $fieldName),
            ];
        }

        return [
            'type' => 'object',
            'properties' => [
                'criteria' => [
                    'type' => 'object',
                    'properties' => $criteriaProperties,
                    'required' => array_keys($criteriaProperties),
                    'additionalProperties' => false,
                    'description' => 'Filter criteria. Set each filter to null unless the user explicitly mentioned it.',
                ],
                'sorting' => [
                    'type' => 'object',
                    'properties' => $sortingProperties,
                    'required' => array_keys($sortingProperties),
                    'additionalProperties' => false,
                    'description' => 'Sorting. Set each field to null unless the user explicitly asked to sort by it.',
                ],
            ],
            'required' => ['criteria', 'sorting'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Build enhanced description with available filters summary.
     *
     * @param array<string, mixed> $gridSchema
     */
    private function buildDescription(array $gridSchema): string
    {
        /** @var array<string, array<string, mixed>> $filters */
        $filters = $gridSchema['filters'];
        /** @var array<string, array<string, mixed>> $sortableFields */
        $sortableFields = $gridSchema['sortable_fields'];

        $sortableList = array_keys($sortableFields);

        $filtersDescription = '';
        foreach ($filters as $name => $schema) {
            $filtersDescription .= sprintf("- %s: %s\n", $name, $schema['description'] ?? $name);
        }

        return sprintf(
            "Generate a URL to filter and sort the grid.\n\n" .
            "Available filters (include ONLY those explicitly mentioned by the user):\n%s\n" .
            'Sortable fields: %s',
            $filtersDescription,
            implode(', ', $sortableList),
        );
    }
}
