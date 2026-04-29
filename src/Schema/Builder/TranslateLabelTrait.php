<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

trait TranslateLabelTrait
{
    protected function translateLabel(string|bool|null $label): string
    {
        if (null === $label || false === $label || true === $label) {
            return '';
        }

        return $this->translator->trans($label);
    }
}
