<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Validator;

use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Definition\Grid;

final readonly class GridSortingValidator implements GridSortingValidatorInterface
{
    public function __construct(
        private LoggerInterface $aiLogger,
    ) {
    }

    public function validate(array $rawSorting, Grid $grid): array
    {
        $sortableFields = [];
        foreach ($grid->getFields() as $field) {
            if ($field->isSortable()) {
                $sortableFields[] = $field->getName();
            }
        }

        $valid = [];
        foreach ($rawSorting as $field => $direction) {
            if (null === $direction) {
                continue;
            }

            if (!in_array($field, $sortableFields, true)) {
                $this->aiLogger->warning('[GridAssistant] Unknown sortable field skipped', ['field' => $field]);

                continue;
            }

            if (!is_string($direction)) {
                continue;
            }

            $normalized = strtolower(trim($direction));
            if (!in_array($normalized, ['asc', 'desc'], true)) {
                $normalized = 'asc';
            }

            $valid[$field] = $normalized;
        }

        return $valid;
    }
}
