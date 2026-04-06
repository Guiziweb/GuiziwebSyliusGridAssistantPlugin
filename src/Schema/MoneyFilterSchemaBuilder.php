<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema;

use Sylius\Component\Currency\Repository\CurrencyRepositoryInterface;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MoneyFilterSchemaBuilder extends AbstractFilterSchemaBuilder
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly CurrencyRepositoryInterface $currencyRepository,
    ) {
        parent::__construct($translator);
    }

    public static function getType(): string
    {
        return 'money';
    }

    protected function buildSchema(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());
        $options = $filter->getOptions();
        $hasCurrency = isset($options['currency_field']);

        $properties = [
            'greaterThan' => [
                'type' => 'number',
                'description' => 'Minimum value',
            ],
            'lessThan' => [
                'type' => 'number',
                'description' => 'Maximum value',
            ],
        ];

        if ($hasCurrency) {
            $currencyCodes = $this->getAvailableCurrencyCodes();
            $properties['currency'] = [
                'type' => 'string',
                'enum' => $currencyCodes,
                'description' => sprintf('Currency code. Map user input (symbols, names) to one of: %s', implode(', ', $currencyCodes)),
            ];
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'additionalProperties' => false,
            'description' => sprintf('%s (values in main currency unit, not cents)', $label),
        ];
    }

    /**
     * @return string[]
     */
    private function getAvailableCurrencyCodes(): array
    {
        $currencies = $this->currencyRepository->findAll();
        $codes = [];

        foreach ($currencies as $currency) {
            $codes[] = $currency->getCode();
        }

        return $codes;
    }
}
