<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\StringOperators;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Filter\StringFilter;
use Symfony\Contracts\Translation\TranslatorInterface;

class StringFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    use TranslateLabelTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getType(): string
    {
        return StringFilter::NAME;
    }

    public function build(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());
        $formOptions = $filter->getFormOptions();
        $fixedType = is_string($formOptions['type'] ?? null) ? $formOptions['type'] : null;

        if (null !== $fixedType) {
            return [
                'type' => 'string',
                'description' => $this->buildDescriptionFixed($label, $fixedType),
            ];
        }

        $options = $filter->getOptions();
        $defaultOperator = is_string($options['type'] ?? null) ? $options['type'] : StringFilter::TYPE_CONTAINS;

        return [
            'type' => 'object',
            'properties' => [
                'value' => [
                    'type' => 'string',
                    'description' => $this->buildDescriptionValue(),
                ],
                'type' => [
                    'anyOf' => [['type' => 'string', 'enum' => StringOperators::ALL], ['type' => 'null']],
                    'description' => $this->buildDescriptionOperator($defaultOperator),
                ],
            ],
            'required' => ['value', 'type'],
            'additionalProperties' => false,
            'description' => $this->buildDescription($label),
        ];
    }

    protected function buildDescription(string $label): string
    {
        return sprintf('%s - use {value: "text"} or {value: "text", type: "equal"}. Omit if not mentioned by the user.', $label);
    }

    protected function buildDescriptionFixed(string $label, string $fixedType): string
    {
        return sprintf('%s - search value (%s). Omit if not mentioned by the user.', $label, $fixedType);
    }

    protected function buildDescriptionValue(): string
    {
        return 'The search value. For "in"/"not_in" operators, use comma-separated values (e.g. "foo,bar").';
    }

    protected function buildDescriptionOperator(string $defaultOperator): string
    {
        return sprintf('The comparison operator. null to use default (%s).', $defaultOperator);
    }
}
