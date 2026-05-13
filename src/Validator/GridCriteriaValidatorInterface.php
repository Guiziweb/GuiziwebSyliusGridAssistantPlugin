<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Validator;

use Sylius\Component\Grid\Definition\Grid;

interface GridCriteriaValidatorInterface
{
    /**
     * Validate and format raw criteria coming from the LLM against the actual grid definition.
     *
     * @param array<string, mixed> $rawCriteria
     *
     * @return array<string, mixed>
     */
    public function validate(array $rawCriteria, Grid $grid): array;
}
