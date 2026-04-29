<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Formatter;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\MoneyFilterValueFormatter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;

final class MoneyFilterValueFormatterTest extends TestCase
{
    private MoneyFilterValueFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new MoneyFilterValueFormatter();
    }

    private function filter(): Filter
    {
        return Filter::fromNameAndType('total', 'money');
    }

    public function testFormatGreaterThan(): void
    {
        $result = $this->formatter->format(['greaterThan' => 100.0], $this->filter());

        self::assertSame(['greaterThan' => 100.0], $result->value);
    }

    public function testFormatRange(): void
    {
        $result = $this->formatter->format(['greaterThan' => 50.0, 'lessThan' => 200.0], $this->filter());

        self::assertSame(['greaterThan' => 50.0, 'lessThan' => 200.0], $result->value);
    }

    public function testFormatWithCurrencyNormalized(): void
    {
        $result = $this->formatter->format(['greaterThan' => 100.0, 'currency' => 'eur'], $this->filter());

        self::assertSame(['greaterThan' => 100.0, 'currency' => 'EUR'], $result->value);
    }

    public function testFormatCurrencyOnly(): void
    {
        $result = $this->formatter->format(['currency' => 'USD'], $this->filter());

        self::assertSame(['currency' => 'USD'], $result->value);
    }

    public function testFormatEmptyArrayReturnsNull(): void
    {
        $result = $this->formatter->format([], $this->filter());

        self::assertNull($result->value);
    }

    public function testFormatNonArrayReturnsNull(): void
    {
        $result = $this->formatter->format(100.0, $this->filter());

        self::assertNull($result->value);
    }
}