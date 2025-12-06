<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    use TranslateLabelTrait;

    public function __construct(
        protected readonly TranslatorInterface $translator,
    ) {
    }

    public function build(Filter $filter): array
    {
        return $this->buildSchema($filter);
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function buildSchema(Filter $filter): array;
}