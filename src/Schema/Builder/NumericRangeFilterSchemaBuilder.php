<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

class NumericRangeFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    use TranslateLabelTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getType(): string
    {
        return 'numeric_range';
    }

    public function build(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());

        return [
            'type' => 'object',
            'properties' => [
                'greaterThan' => [
                    'anyOf' => [['type' => 'number'], ['type' => 'null']],
                    'description' => $this->buildDescriptionGreaterThan(),
                ],
                'lessThan' => [
                    'anyOf' => [['type' => 'number'], ['type' => 'null']],
                    'description' => $this->buildDescriptionLessThan(),
                ],
            ],
            'required' => ['greaterThan', 'lessThan'],
            'additionalProperties' => false,
            'description' => $this->buildDescription($label),
        ];
    }

    protected function buildDescription(string $label): string
    {
        return $label . ' Omit if not mentioned by the user.';
    }

    protected function buildDescriptionGreaterThan(): string
    {
        return 'Minimum value. null if not mentioned.';
    }

    protected function buildDescriptionLessThan(): string
    {
        return 'Maximum value. null if not mentioned.';
    }
}
