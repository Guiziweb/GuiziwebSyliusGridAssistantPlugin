<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Toolbox;

use Guiziweb\SyliusGridAssistantPlugin\Toolbox\ToolDescriptionEnricherInterface;
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
     * @return array<string, mixed>
     */
    private function buildParametersSchema(array $gridSchema): array
    {
        $sortingProperties = [];

        foreach ($gridSchema['sortable_fields'] as $fieldName => $fieldConfig) {
            $sortingProperties[$fieldName] = [
                'type' => 'string',
                'enum' => ['asc', 'desc'],
                'description' => sprintf('Sort by %s', $fieldConfig['label'] ?? $fieldName),
            ];
        }

        return [
            'type' => 'object',
            'properties' => [
                'criteria' => [
                    'type' => 'object',
                    'properties' => $gridSchema['filters'],
                    'additionalProperties' => false,
                    'description' => 'Filter criteria - only use the properties defined here',
                ],
                'sorting' => [
                    'type' => 'object',
                    'properties' => $sortingProperties,
                    'additionalProperties' => false,
                    'description' => 'Sorting configuration',
                ],
            ],
            'required' => ['criteria'],
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
        $filtersList = array_keys($gridSchema['filters']);
        $sortableList = array_keys($gridSchema['sortable_fields']);

        return sprintf(
            "Generate a URL to filter and sort the grid.\n\n" .
            "Available filters: %s\n\n" .
            "Sortable fields: %s",
            implode(', ', $filtersList),
            implode(', ', $sortableList),
        );
    }
}
