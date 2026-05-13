<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Validator;

use Sylius\Component\Grid\Definition\Grid;

interface GridSortingValidatorInterface
{
    /**
     * Validate raw sorting coming from the LLM, keeping only sortable fields with a valid asc/desc direction.
     *
     * @param array<string, mixed> $rawSorting
     *
     * @return array<string, string>
     */
    public function validate(array $rawSorting, Grid $grid): array;
}
