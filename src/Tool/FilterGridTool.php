<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tool;

use Doctrine\ORM\EntityManagerInterface;
use Guiziweb\SyliusGridAssistantPlugin\Context\GridContext;
use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Autocomplete\AutocompleterRegistry;
use Symfony\UX\Autocomplete\OptionsAwareEntityAutocompleterInterface;

#[AsTool(
    name: 'filter_grid',
    description: <<<'DESC'
Generate a URL to redirect the user to a filtered and sorted grid.

Parameters:
- criteria: Filter criteria as key-value pairs. The format depends on the filter type:
  - string: {"value": "text", "type": "contains"} or just "text"
  - boolean: "true" or "false"
  - select: "choice_value" or ["value1", "value2"] for multiple
  - date: {"from": {"date": "2024-01-01"}, "to": {"date": "2024-12-31"}}
  - money/range: {"greaterThan": 1000, "lessThan": 5000}
  - entity: entity_id (integer) - use search_entity to find IDs first
- sorting: Optional sorting as key-value pairs, e.g., {"date": "desc", "total": "asc"}

Returns the redirect URL.
DESC,
)]
final class FilterGridTool
{
    private const array ENTITY_FILTER_TYPES = [
        'entity',
        'ux_autocomplete',
        'ux_translatable_autocomplete',
        'resource_autocomplete',
    ];

    /** @var string[] */
    private array $warnings = [];

    public function __construct(
        private readonly GridContext $gridContext,
        private readonly EntityManagerInterface $entityManager,
        private readonly AutocompleterRegistry $autocompleterRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $aiLogger,
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'sylius.grid.chain_provider')]
        private readonly GridProviderInterface $gridProvider,
    ) {
    }

    /**
     * @param array<string, mixed>       $criteria Filter criteria - format depends on filter type
     * @param array<string, string|null> $sorting  Sorting configuration, e.g., ["date" => "desc"]
     *
     * @return array{redirect_url: string, warnings?: string[]}|array{error: string}
     */
    public function __invoke(
        array $criteria = [],
        array $sorting = [],
    ): array {
        $this->warnings = [];

        if (!$this->gridContext->hasContext()) {
            return ['error' => 'No grid context available. Cannot filter.'];
        }

        $gridCode = (string) $this->gridContext->getGridCode();
        $routeName = (string) $this->gridContext->getRouteName();
        $routeParams = $this->gridContext->getRouteParams();

        $this->aiLogger->info('[FilterGridTool] Invoked', [
            'gridCode' => $gridCode,
            'routeName' => $routeName,
            'criteria' => $criteria,
            'sorting' => $sorting,
        ]);

        try {
            $grid = $this->gridProvider->get($gridCode);
        } catch (\InvalidArgumentException) {
            return ['error' => sprintf("Grid '%s' not found.", $gridCode)];
        }

        // Validate and format criteria using Grid definition directly
        try {
            $validCriteria = $this->validateAndFormatCriteria($criteria, $grid);
        } catch (\Throwable $e) {
            $this->aiLogger->error('[FilterGridTool] Failed to validate criteria', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $error = ['error' => sprintf('Failed to validate criteria: %s', $e->getMessage())];
            $this->gridContext->setResult($error);

            return $error;
        }

        // Validate sorting using Grid definition directly
        $validSorting = $this->validateSorting($sorting, $grid);

        // Build URL parameters
        $urlParams = $routeParams;

        if (!empty($validCriteria)) {
            $urlParams['criteria'] = $validCriteria;
        }

        if (!empty($validSorting)) {
            $urlParams['sorting'] = $validSorting;
        }

        $this->aiLogger->info('[FilterGridTool] Generating URL', [
            'validCriteria' => $validCriteria,
            'validSorting' => $validSorting,
            'urlParams' => $urlParams,
        ]);

        try {
            $url = $this->urlGenerator->generate($routeName, $urlParams);
            $result = ['redirect_url' => $url];

            if (!empty($this->warnings)) {
                $result['warnings'] = $this->warnings;
            }

            $this->gridContext->setResult($result);

            return $result;
        } catch (\Exception $e) {
            $error = ['error' => sprintf('Failed to generate URL: %s', $e->getMessage())];
            $this->gridContext->setResult($error);

            return $error;
        }
    }

    /**
     * Validate and format criteria based on Grid definition.
     *
     * @param array<string, mixed> $criteria
     *
     * @return array<string, mixed>
     */
    private function validateAndFormatCriteria(array $criteria, Grid $grid): array
    {
        $validCriteria = [];

        foreach ($criteria as $filterName => $value) {
            if (!$grid->hasFilter($filterName)) {
                $this->aiLogger->warning('[FilterGridTool] Unknown filter skipped', [
                    'filter' => $filterName,
                ]);

                continue;
            }

            $filter = $grid->getFilter($filterName);
            $formatted = $this->formatCriterion($value, $filter);

            if (null !== $formatted) {
                $validCriteria[$filterName] = $formatted;
            }
        }

        return $validCriteria;
    }

    /**
     * Format a criterion value based on filter type.
     */
    private function formatCriterion(mixed $value, Filter $filter): mixed
    {
        $filterType = $filter->getType();

        return match ($filterType) {
            'string' => $this->formatStringCriterion($value, $filter),
            'boolean', 'exists' => $this->formatBooleanCriterion($value),
            'select' => $this->formatSelectCriterion($value, $filter),
            'date' => $this->formatDateCriterion($value),
            'money', 'numeric_range' => $this->formatRangeCriterion($value),
            default => in_array($filterType, self::ENTITY_FILTER_TYPES, true)
                ? $this->formatEntityCriterion($value, $filter)
                : $value,
        };
    }

    /**
     * Format string filter criterion.
     *
     * @return array{type: string, value: string}|null
     */
    private function formatStringCriterion(mixed $value, Filter $filter): ?array
    {
        $options = $filter->getOptions();
        $defaultOperator = is_string($options['type'] ?? null) ? (string) $options['type'] : 'contains';

        if (is_array($value)) {
            $type = is_string($value['type'] ?? null) ? (string) $value['type'] : $defaultOperator;
            $val = is_scalar($value['value'] ?? null) ? (string) $value['value'] : '';
        } else {
            $type = $defaultOperator;
            $val = is_scalar($value) ? (string) $value : '';
        }

        if ('' === $val && !in_array($type, ['empty', 'not_empty'], true)) {
            return null;
        }

        return [
            'type' => $type,
            'value' => $val,
        ];
    }

    /**
     * Format boolean/exists filter criterion.
     */
    private function formatBooleanCriterion(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['true', '1', 'yes', 'oui'], true)) {
                return 'true';
            }
            if (in_array($normalized, ['false', '0', 'no', 'non'], true)) {
                return 'false';
            }
        }

        return null;
    }

    /**
     * Format select filter criterion.
     */
    private function formatSelectCriterion(mixed $value, Filter $filter): mixed
    {
        $formOptions = $filter->getFormOptions();
        // Choices are defined as [label => value], we need the values
        /** @var array<mixed> $choicesRaw */
        $choicesRaw = is_array($formOptions['choices'] ?? null) ? $formOptions['choices'] : [];
        $choices = array_values($choicesRaw);
        $isMultiple = (bool) ($formOptions['multiple'] ?? false);

        if ($isMultiple && is_array($value)) {
            return array_values(array_filter($value, fn ($v) => in_array($v, $choices, true)));
        }

        if (is_string($value) && in_array($value, $choices, true)) {
            return $value;
        }

        // Accept the value as-is if no choices defined (dynamic choices)
        if (empty($choices)) {
            return $value;
        }

        return null;
    }

    /**
     * Format date filter criterion.
     * Accepts 'start'/'end' from LLM and converts to 'from'/'to' for Sylius.
     *
     * @return array<string, array{date: string, time?: string}>|null
     */
    private function formatDateCriterion(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $result = [];

        // Accept 'start' (from LLM schema) and convert to 'from' (Sylius format)
        if (isset($value['start'])) {
            $from = $this->formatDatePart($value['start']);
            if (null !== $from) {
                $result['from'] = $from;
            }
        }

        // Accept 'end' (from LLM schema) and convert to 'to' (Sylius format)
        if (isset($value['end'])) {
            $to = $this->formatDatePart($value['end']);
            if (null !== $to) {
                $result['to'] = $to;
            }
        }

        return !empty($result) ? $result : null;
    }

    /**
     * Format a date part (from or to).
     *
     * @return array{date: string, time?: string}|null
     */
    private function formatDatePart(mixed $datePart): ?array
    {
        if (is_string($datePart)) {
            // Simple date string
            return ['date' => $datePart];
        }

        if (is_array($datePart) && isset($datePart['date'])) {
            $result = ['date' => $datePart['date']];
            if (isset($datePart['time'])) {
                $result['time'] = $datePart['time'];
            }

            return $result;
        }

        return null;
    }

    /**
     * Format range filter criterion (money, numeric_range).
     *
     * @return array{greaterThan?: int|float, lessThan?: int|float, currency?: string}|null
     */
    private function formatRangeCriterion(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $result = [];

        if (isset($value['greaterThan']) && is_numeric($value['greaterThan']) && $value['greaterThan'] > 0) {
            $result['greaterThan'] = (float) $value['greaterThan'];
        }

        if (isset($value['lessThan']) && is_numeric($value['lessThan']) && $value['lessThan'] > 0) {
            $result['lessThan'] = (float) $value['lessThan'];
        }

        // Handle alternative key names
        if (isset($value['min']) && is_numeric($value['min']) && $value['min'] > 0) {
            $result['greaterThan'] = (float) $value['min'];
        }

        if (isset($value['max']) && is_numeric($value['max']) && $value['max'] > 0) {
            $result['lessThan'] = (float) $value['max'];
        }

        // Handle currency for money filter
        if (isset($value['currency']) && is_string($value['currency'])) {
            $result['currency'] = strtoupper(trim($value['currency']));
        }

        return !empty($result) ? $result : null;
    }

    /**
     * Format entity filter criterion.
     * If value is a string (not an ID), search for the entity.
     */
    private function formatEntityCriterion(mixed $value, Filter $filter): mixed
    {
        $formOptions = $filter->getFormOptions();
        $isMultiple = $formOptions['multiple'] ?? false;

        // Multiple values provided as array
        if (is_array($value)) {
            $ids = [];
            foreach ($value as $v) {
                $resolved = $this->resolveEntityId($v, $filter);
                if (null !== $resolved) {
                    $ids[] = $resolved;
                }
            }

            return !empty($ids) ? $ids : null;
        }

        // Single value - resolve to ID
        $id = $this->resolveEntityId($value, $filter);
        if (null === $id) {
            return null;
        }

        // If filter expects multiple values, wrap in array
        return $isMultiple ? [$id] : $id;
    }

    /**
     * Resolve a single value to an entity ID using Sylius autocomplete mechanism.
     */
    private function resolveEntityId(mixed $value, Filter $filter): ?int
    {
        // Already an ID
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        // String value - search for entity using autocomplete
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        $filterType = $filter->getType();
        $autocompleterAlias = $this->getAutocompleterAlias($filterType);

        $this->aiLogger->info('[FilterGridTool] Searching entity', [
            'query' => $value,
            'filterType' => $filterType,
            'autocompleterAlias' => $autocompleterAlias,
            'formOptions' => $filter->getFormOptions(),
        ]);

        $autocompleter = $this->autocompleterRegistry->getAutocompleter($autocompleterAlias);
        if (null === $autocompleter) {
            $this->aiLogger->warning('[FilterGridTool] Autocompleter not found', [
                'alias' => $autocompleterAlias,
            ]);

            return null;
        }

        // Reset autocompleter state to allow setting new options
        if ($autocompleter instanceof ResetInterface) {
            $autocompleter->reset();
        }

        // Pass filter form options to the autocompleter
        if ($autocompleter instanceof OptionsAwareEntityAutocompleterInterface) {
            $autocompleter->setOptions($filter->getFormOptions());
        }

        try {
            $entityClass = $autocompleter->getEntityClass();
        } catch (\Throwable $e) {
            $this->aiLogger->error('[FilterGridTool] Failed to get entity class', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }

        $this->aiLogger->debug('[FilterGridTool] Autocompleter entity class', [
            'entityClass' => $entityClass,
        ]);

        $repository = $this->entityManager->getRepository($entityClass);

        try {
            $qb = $autocompleter->createFilteredQueryBuilder($repository, $value);
        } catch (\Throwable $e) {
            $this->aiLogger->error('[FilterGridTool] Failed to create query builder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }

        $qb->setMaxResults(1);

        $this->aiLogger->debug('[FilterGridTool] Query', [
            'dql' => $qb->getDQL(),
            'params' => array_map(fn ($p) => $p->getValue(), $qb->getParameters()->toArray()),
        ]);

        try {
            /** @var array<object> $results */
            $results = $qb->getQuery()->getResult() ?? [];
            if (!empty($results)) {
                $entity = $results[0];
                $id = $autocompleter->getValue($entity);

                $this->aiLogger->info('[FilterGridTool] Found entity', [
                    'query' => $value,
                    'id' => $id,
                ]);

                return is_scalar($id) ? (int) $id : 0;
            }
        } catch (\Exception $e) {
            $this->aiLogger->warning('[FilterGridTool] Entity search failed', [
                'query' => $value,
                'error' => $e->getMessage(),
            ]);
        }

        $this->aiLogger->warning('[FilterGridTool] Entity not found', [
            'query' => $value,
            'filterType' => $filterType,
        ]);

        $label = $this->translateFilterLabel($filter);
        $this->warnings[] = sprintf('%s "%s" not found', $label, $value);

        return null;
    }

    /**
     * Get the autocompleter alias for a filter type.
     */
    private function getAutocompleterAlias(string $filterType): string
    {
        return match ($filterType) {
            'ux_translatable_autocomplete' => 'sylius_admin_grid_filter_translatable_autocomplete',
            'ux_autocomplete' => 'sylius_admin_grid_filter_autocomplete',
            default => 'sylius_admin_grid_filter_autocomplete',
        };
    }

    /**
     * Validate sorting configuration.
     *
     * @param array<string, string|null> $sorting
     *
     * @return array<string, string>
     */
    private function validateSorting(array $sorting, Grid $grid): array
    {
        $validSorting = [];
        $sortableFields = $this->getSortableFields($grid);

        foreach ($sorting as $field => $direction) {
            if (null === $direction) {
                continue;
            }

            if (!in_array($field, $sortableFields, true)) {
                $this->aiLogger->warning('[FilterGridTool] Unknown sortable field skipped', [
                    'field' => $field,
                ]);

                continue;
            }

            $normalizedDirection = strtolower(trim((string) $direction));
            if (!in_array($normalizedDirection, ['asc', 'desc'], true)) {
                $normalizedDirection = 'asc';
            }

            $validSorting[$field] = $normalizedDirection;
        }

        return $validSorting;
    }

    /**
     * Get sortable field names from grid.
     *
     * @return string[]
     */
    private function getSortableFields(Grid $grid): array
    {
        $sortableFields = [];

        foreach ($grid->getFields() as $field) {
            if ($field->isSortable()) {
                $sortableFields[] = $field->getName();
            }
        }

        return $sortableFields;
    }

    private function translateFilterLabel(Filter $filter): string
    {
        $label = $filter->getLabel();

        if (!is_string($label) || '' === $label) {
            return $filter->getName();
        }

        return $this->translator->trans($label);
    }
}
