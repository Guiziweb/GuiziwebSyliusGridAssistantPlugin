<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

class SelectFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    use TranslateLabelTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getType(): string|array
    {
        return ['select', 'enum'];
    }

    public function build(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());
        $formOptions = $filter->getFormOptions();
        /** @var array<string, mixed> $choices */
        $choices = is_array($formOptions['choices'] ?? null) ? $formOptions['choices'] : [];
        $isMultiple = (bool) ($formOptions['multiple'] ?? false);

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
                'description' => $this->buildDescriptionMultiple($label),
            ];
        }

        return [
            'type' => 'string',
            'enum' => $choiceValues,
            'description' => $this->buildDescription($label),
        ];
    }

    protected function buildDescription(string $label): string
    {
        return $label . ' Omit if not mentioned by the user.';
    }

    protected function buildDescriptionMultiple(string $label): string
    {
        return sprintf('%s (multiple selection allowed). Omit if not mentioned by the user.', $label);
    }
}
