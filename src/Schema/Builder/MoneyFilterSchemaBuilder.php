<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Schema\Builder;

use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Currency\Repository\CurrencyRepositoryInterface;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

class MoneyFilterSchemaBuilder implements FilterSchemaBuilderInterface
{
    use TranslateLabelTrait;

    /**
     * @param CurrencyRepositoryInterface<CurrencyInterface> $currencyRepository
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CurrencyRepositoryInterface $currencyRepository,
    ) {
    }

    public static function getType(): string
    {
        return 'money';
    }

    public function build(Filter $filter): array
    {
        $label = $this->translateLabel($filter->getLabel());
        $options = $filter->getOptions();
        $hasCurrency = isset($options['currency_field']);

        $properties = [
            'greaterThan' => [
                'anyOf' => [['type' => 'number'], ['type' => 'null']],
                'description' => $this->buildDescriptionGreaterThan(),
            ],
            'lessThan' => [
                'anyOf' => [['type' => 'number'], ['type' => 'null']],
                'description' => $this->buildDescriptionLessThan(),
            ],
        ];
        $required = ['greaterThan', 'lessThan'];

        if ($hasCurrency) {
            $currencyCodes = $this->getAvailableCurrencyCodes();
            $properties['currency'] = [
                'anyOf' => [['type' => 'string', 'enum' => $currencyCodes], ['type' => 'null']],
                'description' => $this->buildDescriptionCurrency($currencyCodes),
            ];
            $required[] = 'currency';
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false,
            'description' => $this->buildDescription($label),
        ];
    }

    protected function buildDescription(string $label): string
    {
        return sprintf('%s (values in main currency unit, not cents). Omit if not mentioned by the user.', $label);
    }

    protected function buildDescriptionGreaterThan(): string
    {
        return 'Minimum value. null if not mentioned.';
    }

    protected function buildDescriptionLessThan(): string
    {
        return 'Maximum value. null if not mentioned.';
    }

    /**
     * @param string[] $currencyCodes
     */
    protected function buildDescriptionCurrency(array $currencyCodes): string
    {
        return sprintf('Currency code. Map user input (symbols, names) to one of: %s. null if not mentioned.', implode(', ', $currencyCodes));
    }

    /**
     * @return string[]
     */
    private function getAvailableCurrencyCodes(): array
    {
        $currencies = $this->currencyRepository->findAll();
        $codes = [];

        foreach ($currencies as $currency) {
            if ($currency instanceof CurrencyInterface) {
                $codes[] = (string) $currency->getCode();
            }
        }

        return $codes;
    }
}
