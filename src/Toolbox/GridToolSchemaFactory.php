<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Toolbox;

final class GridToolSchemaFactory
{
    /**
     * @param array<string, mixed> $gridSchema
     *
     * @return array{type: 'object', properties: array<string, array<string, mixed>>, required: array<int, string>, additionalProperties: false}
     */
    public function buildParameters(array $gridSchema): array
    {
        /** @var array<string, array<string, mixed>> $filters */
        $filters = $gridSchema['filters'];
        /** @var array<string, array<string, mixed>> $sortableFields */
        $sortableFields = $gridSchema['sortable_fields'];

        $criteriaProperties = [];
        $criteriaRequired = [];
        foreach ($filters as $filterName => $filterSchema) {
            $description = is_string($filterSchema['description'] ?? null) ? $filterSchema['description'] : $filterName;
            unset($filterSchema['description']);

            $criteriaProperties[$filterName] = [
                'anyOf' => [$filterSchema, ['type' => 'null']],
                'description' => $description,
            ];
            $criteriaRequired[] = $filterName;
        }

        $sortingProperties = [];
        $sortingRequired = [];
        foreach ($sortableFields as $fieldName => $fieldConfig) {
            $sortingProperties[$fieldName] = [
                'anyOf' => [
                    ['type' => 'string', 'enum' => ['asc', 'desc']],
                    ['type' => 'null'],
                ],
                'description' => sprintf(
                    'Sort by %s. null if not mentioned by the user.',
                    is_string($fieldConfig['label'] ?? null) ? $fieldConfig['label'] : $fieldName,
                ),
            ];
            $sortingRequired[] = $fieldName;
        }

        return [
            'type' => 'object',
            'properties' => [
                'criteria' => [
                    'type' => 'object',
                    'properties' => $criteriaProperties,
                    'required' => $criteriaRequired,
                    'additionalProperties' => false,
                    'description' => 'Filter criteria. Set to null any filter not mentioned by the user.',
                ],
                'sorting' => [
                    'type' => 'object',
                    'properties' => $sortingProperties,
                    'required' => $sortingRequired,
                    'additionalProperties' => false,
                    'description' => 'Sorting. Set to null any field the user did not ask to sort by.',
                ],
                'message' => [
                    'anyOf' => [['type' => 'string'], ['type' => 'null']],
                    'description' => 'Natural language message to display to the user. Use this to explain what filters were applied, or to explain why no filters could be applied (e.g. query too vague). null if no message is needed.',
                ],
            ],
            'required' => ['criteria', 'sorting', 'message'],
            'additionalProperties' => false,
        ];
    }
}
