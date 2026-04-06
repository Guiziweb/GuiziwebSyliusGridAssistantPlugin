<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Filter;

final class SelectFilterSchemaBuilder extends AbstractFilterSchemaBuilder
{
    public static function getType(): string
    {
        return 'select';
    }

    protected function buildSchema(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());
        $formOptions = $filter->getFormOptions();
        /** @var array<string, mixed> $choices */
        $choices = is_array($formOptions['choices'] ?? null) ? $formOptions['choices'] : [];
        $isMultiple = (bool) ($formOptions['multiple'] ?? false);

        // Translate and get choice values
        $translatedChoices = [];
        foreach ($choices as $choiceLabel => $value) {
            $translatedChoices[$this->translator->trans((string) $choiceLabel)] = $value;
        }
        $choiceValues = array_values($translatedChoices);

        if ($isMultiple) {
            return [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'enum' => $choiceValues,
                ],
                'description' => sprintf('%s (multiple selection allowed)', $label),
            ];
        }

        return [
            'type' => 'string',
            'enum' => $choiceValues,
            'description' => $label,
        ];
    }
}
