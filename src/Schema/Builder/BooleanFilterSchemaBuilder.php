<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    use TranslateLabelTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getType(): string
    {
        return 'boolean';
    }

    public function build(Filter $filter): array
    {
        return [
            'type' => 'boolean',
            'description' => $this->buildDescription($this->translateLabel($filter->getLabel())),
        ];
    }

    protected function buildDescription(string $label): string
    {
        return $label . ' Omit if not mentioned by the user.';
    }
}
