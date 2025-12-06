<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EntityFilterSchemaBuilder extends AbstractFilterSchemaBuilder
{
    public function __construct(
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    public static function getType(): array
    {
        return [
            'entity',
            'ux_autocomplete',
            'ux_translatable_autocomplete',
            'resource_autocomplete',
        ];
    }

    protected function buildSchema(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());
        $formOptions = $filter->getFormOptions();

        $extraOptions = $formOptions['extra_options'] ?? [];
        $choiceLabel = $extraOptions['choice_label'] ?? 'name';
        $isMultiple = $formOptions['multiple'] ?? false;

        if ($isMultiple) {
            return [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => sprintf('%s - search by %s. Accepts multiple values.', $label, $choiceLabel),
            ];
        }

        return [
            'type' => 'string',
            'description' => sprintf('%s - search by %s', $label, $choiceLabel),
        ];
    }
}