<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\MoneyFilterSchemaBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Currency\Repository\CurrencyRepositoryInterface;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MoneyFilterSchemaBuilderTest extends TestCase
{
    private MoneyFilterSchemaBuilder $builder;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $eur = new Currency();
        $eur->setCode('EUR');
        $usd = new Currency();
        $usd->setCode('USD');

        $currencyRepository = $this->createMock(CurrencyRepositoryInterface::class);
        $currencyRepository->method('findAll')->willReturn([$eur, $usd]);

        $this->builder = new MoneyFilterSchemaBuilder($translator, $currencyRepository);
    }

    private function filter(string $label = 'Total', array $options = []): Filter
    {
        $filter = Filter::fromNameAndType('total', 'money');
        $filter->setLabel($label);
        $filter->setOptions($options);

        return $filter;
    }

    public function testBuildReturnsObjectType(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertSame('object', $schema['type']);
    }

    public function testBuildHasRangeProperties(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertArrayHasKey('greaterThan', $schema['properties']);
        self::assertArrayHasKey('lessThan', $schema['properties']);
    }

    public function testBuildWithCurrencyFieldIncludesCurrencyProperty(): void
    {
        $filter = $this->filter('Total', ['currency_field' => 'currencyCode']);

        $schema = $this->builder->build($filter);

        self::assertArrayHasKey('currency', $schema['properties']);
        self::assertSame(['EUR', 'USD'], $schema['properties']['currency']['anyOf'][0]['enum']);
    }

    public function testBuildWithoutCurrencyFieldExcludesCurrencyProperty(): void
    {
        $schema = $this->builder->build($this->filter());

        self::assertArrayNotHasKey('currency', $schema['properties']);
    }
}