<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\Autocomplete\AutocompleterRegistry;
use Symfony\UX\Autocomplete\OptionsAwareEntityAutocompleterInterface;

final class EntityFilterValueFormatter implements FilterValueFormatterInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AutocompleterRegistry $autocompleterRegistry,
        private readonly LoggerInterface $aiLogger,
    ) {
    }

    public static function getType(): string|array
    {
        return [
            'entity',
            'ux_autocomplete',
            'ux_translatable_autocomplete',
            'resource_autocomplete',
        ];
    }

    public function format(mixed $value, Filter $filter): FilterFormatResult
    {
        $formOptions = $filter->getFormOptions();
        $isMultiple = (bool) ($formOptions['multiple'] ?? false);

        if (is_array($value)) {
            $ids = [];
            $warnings = [];
            foreach ($value as $v) {
                $resolved = $this->resolveEntityId($v, $filter);
                if (null !== $resolved->value) {
                    if (is_array($resolved->value)) {
                        array_push($ids, ...$resolved->value);
                    } else {
                        $ids[] = $resolved->value;
                    }
                }
                array_push($warnings, ...$resolved->warnings);
            }

            return new FilterFormatResult(!empty($ids) ? $ids : null, $warnings);
        }

        $resolved = $this->resolveEntityId($value, $filter);
        if (null === $resolved->value) {
            return new FilterFormatResult(null, $resolved->warnings);
        }

        if ($isMultiple) {
            return new FilterFormatResult(is_array($resolved->value) ? $resolved->value : [$resolved->value], $resolved->warnings);
        }

        return new FilterFormatResult($resolved->value, $resolved->warnings);
    }

    private function resolveEntityId(mixed $value, Filter $filter): FilterFormatResult
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return new FilterFormatResult((int) $value);
        }

        if (!is_string($value) || '' === trim($value)) {
            return new FilterFormatResult(null);
        }

        $filterType = $filter->getType();
        $autocompleterAlias = $this->getAutocompleterAlias($filterType);

        $this->aiLogger->info('[EntityFilterValueFormatter] Searching entity', [
            'query' => $value,
            'filterType' => $filterType,
            'autocompleterAlias' => $autocompleterAlias,
            'formOptions' => $filter->getFormOptions(),
        ]);

        $autocompleter = $this->autocompleterRegistry->getAutocompleter($autocompleterAlias);
        if (null === $autocompleter) {
            $this->aiLogger->warning('[EntityFilterValueFormatter] Autocompleter not found', [
                'alias' => $autocompleterAlias,
            ]);

            return new FilterFormatResult(null);
        }

        if ($autocompleter instanceof ResetInterface) {
            $autocompleter->reset();
        }

        if ($autocompleter instanceof OptionsAwareEntityAutocompleterInterface) {
            $autocompleter->setOptions($filter->getFormOptions());
        }

        try {
            $entityClass = $autocompleter->getEntityClass();
        } catch (\Throwable $e) {
            $this->aiLogger->error('[EntityFilterValueFormatter] Failed to get entity class', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new FilterFormatResult(null);
        }

        $repository = $this->entityManager->getRepository($entityClass);

        try {
            $qb = $autocompleter->createFilteredQueryBuilder($repository, $value);
        } catch (\Throwable $e) {
            $this->aiLogger->error('[EntityFilterValueFormatter] Failed to create query builder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new FilterFormatResult(null);
        }

        $qb->setMaxResults(1);

        try {
            /** @var array<object> $results */
            $results = $qb->getQuery()->getResult() ?? [];
            if (!empty($results)) {
                $entity = $results[0];
                $id = $autocompleter->getValue($entity);

                $this->aiLogger->info('[EntityFilterValueFormatter] Found entity', [
                    'query' => $value,
                    'id' => $id,
                ]);

                return new FilterFormatResult(is_scalar($id) ? (int) $id : 0);
            }
        } catch (\Exception $e) {
            $this->aiLogger->warning('[EntityFilterValueFormatter] Entity search failed', [
                'query' => $value,
                'error' => $e->getMessage(),
            ]);
        }

        $this->aiLogger->warning('[EntityFilterValueFormatter] Entity not found', [
            'query' => $value,
            'filterType' => $filterType,
        ]);

        return new FilterFormatResult(null, [sprintf('"%s" not found', $value)]);
    }

    private function getAutocompleterAlias(string $filterType): string
    {
        return match ($filterType) {
            'ux_translatable_autocomplete' => 'sylius_admin_grid_filter_translatable_autocomplete',
            default => 'sylius_admin_grid_filter_autocomplete',
        };
    }
}
