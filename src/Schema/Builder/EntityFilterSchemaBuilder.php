<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    use TranslateLabelTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getType(): string|array
    {
        return [
            'entity',
            'ux_autocomplete',
            'ux_translatable_autocomplete',
            'resource_autocomplete',
        ];
    }

    public function build(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());
        $formOptions = $filter->getFormOptions();

        /** @var array<string, mixed> $extraOptions */
        $extraOptions = is_array($formOptions['extra_options'] ?? null) ? $formOptions['extra_options'] : [];
        $choiceLabel = isset($extraOptions['choice_label']) && is_string($extraOptions['choice_label']) ? $extraOptions['choice_label'] : 'name';
        $isMultiple = (bool) ($formOptions['multiple'] ?? false);

        if ($isMultiple) {
            return [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => $this->buildDescriptionMultiple($label, $choiceLabel),
            ];
        }

        return [
            'type' => 'string',
            'description' => $this->buildDescription($label, $choiceLabel),
        ];
    }

    protected function buildDescription(string $label, string $choiceLabel): string
    {
        return sprintf('%s - search by %s. Omit if not mentioned by the user.', $label, $choiceLabel);
    }

    protected function buildDescriptionMultiple(string $label, string $choiceLabel): string
    {
        return sprintf('%s - search by %s. Accepts multiple values. Omit if not mentioned by the user.', $label, $choiceLabel);
    }
}
