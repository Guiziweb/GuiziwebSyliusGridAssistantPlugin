<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Filter\DateFilter;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    use TranslateLabelTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getType(): string
    {
        return DateFilter::NAME;
    }

    public function build(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());

        return [
            'type' => 'object',
            'properties' => [
                'start' => [
                    'anyOf' => [['type' => 'string', 'format' => 'date'], ['type' => 'null']],
                    'description' => $this->buildDescriptionStart(),
                ],
                'end' => [
                    'anyOf' => [['type' => 'string', 'format' => 'date'], ['type' => 'null']],
                    'description' => $this->buildDescriptionEnd(),
                ],
            ],
            'required' => ['start', 'end'],
            'additionalProperties' => false,
            'description' => $this->buildDescription($label),
        ];
    }

    protected function buildDescription(string $label): string
    {
        return sprintf('%s - Convert relative dates (this week, last month, etc.) to YYYY-MM-DD. Omit if not mentioned by the user.', $label);
    }

    protected function buildDescriptionStart(): string
    {
        return 'Start date (YYYY-MM-DD). null if not mentioned.';
    }

    protected function buildDescriptionEnd(): string
    {
        return 'End date (YYYY-MM-DD). null if not mentioned.';
    }
}
