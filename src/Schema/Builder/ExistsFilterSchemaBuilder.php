<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExistsFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    use TranslateLabelTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getType(): string
    {
        return 'exists';
    }

    public function build(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());

        return [
            'type' => 'boolean',
            'description' => $this->buildDescription($label),
        ];
    }

    protected function buildDescription(string $label): string
    {
        return sprintf('%s - true: exists/not null, false: is null. Omit if not mentioned by the user.', $label);
    }
}
