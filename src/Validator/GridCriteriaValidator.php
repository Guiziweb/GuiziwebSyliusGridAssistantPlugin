<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Validator;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\FilterValueFormatterRegistryInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Definition\Grid;

final readonly class GridCriteriaValidator implements GridCriteriaValidatorInterface
{
    public function __construct(
        private FilterValueFormatterRegistryInterface $formatterRegistry,
        private LoggerInterface $aiLogger,
    ) {
    }

    public function validate(array $rawCriteria, Grid $grid): array
    {
        $valid = [];

        foreach ($rawCriteria as $filterName => $value) {
            if (null === $value) {
                continue;
            }

            if (!$grid->hasFilter($filterName)) {
                $this->aiLogger->warning('[GridAssistant] Unknown filter skipped', ['filter' => $filterName]);

                continue;
            }

            $filter = $grid->getFilter($filterName);
            $filterType = $filter->getType();

            if (!$this->formatterRegistry->has($filterType)) {
                $this->aiLogger->warning('[GridAssistant] No formatter registered for filter type, skipping', ['filter' => $filterName, 'type' => $filterType]);

                continue;
            }

            $formatted = $this->formatterRegistry->get($filterType)->format($value, $filter)->value;

            if (null !== $formatted) {
                $valid[$filterName] = $formatted;
            }
        }

        return $valid;
    }
}
