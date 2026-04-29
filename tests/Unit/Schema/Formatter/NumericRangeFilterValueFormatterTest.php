<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Formatter;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\NumericRangeFilterValueFormatter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;

final class NumericRangeFilterValueFormatterTest extends TestCase
{
    private NumericRangeFilterValueFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new NumericRangeFilterValueFormatter();
    }

    private function filter(): Filter
    {
        return Filter::fromNameAndType('price', 'numeric_range');
    }

    public function testFormatGreaterThan(): void
    {
        $result = $this->formatter->format(['greaterThan' => 10], $this->filter());

        self::assertSame(['greaterThan' => '10'], $result->value);
    }

    public function testFormatLessThan(): void
    {
        $result = $this->formatter->format(['lessThan' => 100], $this->filter());

        self::assertSame(['lessThan' => '100'], $result->value);
    }

    public function testFormatRange(): void
    {
        $result = $this->formatter->format(['greaterThan' => 10, 'lessThan' => 100], $this->filter());

        self::assertSame(['greaterThan' => '10', 'lessThan' => '100'], $result->value);
    }

    public function testFormatFloatValues(): void
    {
        $result = $this->formatter->format(['greaterThan' => 9.99, 'lessThan' => 99.99], $this->filter());

        self::assertSame(['greaterThan' => '9.99', 'lessThan' => '99.99'], $result->value);
    }

    public function testFormatGreaterThanZeroIsValid(): void
    {
        $result = $this->formatter->format(['greaterThan' => 0, 'lessThan' => 100], $this->filter());

        self::assertSame(['greaterThan' => '0', 'lessThan' => '100'], $result->value);
    }

    public function testFormatEmptyArrayReturnsNull(): void
    {
        $result = $this->formatter->format([], $this->filter());

        self::assertNull($result->value);
    }

    public function testFormatNonArrayReturnsNull(): void
    {
        $result = $this->formatter->format(42, $this->filter());

        self::assertNull($result->value);
    }
}