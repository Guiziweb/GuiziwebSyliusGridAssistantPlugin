<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Formatter;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\StringFilterValueFormatter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;

final class StringFilterValueFormatterTest extends TestCase
{
    private StringFilterValueFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new StringFilterValueFormatter();
    }

    private function filter(array $options = []): Filter
    {
        $filter = Filter::fromNameAndType('search', 'string');
        $filter->setOptions($options);

        return $filter;
    }

    public function testFormatObjectWithValue(): void
    {
        $result = $this->formatter->format(['value' => 'foo', 'type' => 'equal'], $this->filter());

        self::assertSame(['type' => 'equal', 'value' => 'foo'], $result->value);
    }

    public function testFormatObjectUsesDefaultOperatorContains(): void
    {
        $result = $this->formatter->format(['value' => 'foo'], $this->filter());

        self::assertSame(['type' => 'contains', 'value' => 'foo'], $result->value);
    }

    public function testFormatObjectUsesFilterConfiguredOperator(): void
    {
        $result = $this->formatter->format(['value' => 'foo'], $this->filter(['type' => 'equal']));

        self::assertSame(['type' => 'equal', 'value' => 'foo'], $result->value);
    }

    public function testFormatObjectWithEmptyValueReturnsNull(): void
    {
        $result = $this->formatter->format(['value' => ''], $this->filter());

        self::assertNull($result->value);
    }

    public function testFormatEmptyOperatorIsValidWithoutValue(): void
    {
        $result = $this->formatter->format(['value' => '', 'type' => 'empty'], $this->filter());

        self::assertSame(['type' => 'empty', 'value' => ''], $result->value);
    }

    public function testFormatNotEmptyOperatorIsValidWithoutValue(): void
    {
        $result = $this->formatter->format(['value' => '', 'type' => 'not_empty'], $this->filter());

        self::assertSame(['type' => 'not_empty', 'value' => ''], $result->value);
    }

    public function testFormatInOperatorWithCommaSeparatedValues(): void
    {
        $result = $this->formatter->format(['value' => 'foo,bar,baz', 'type' => 'in'], $this->filter());

        self::assertSame(['type' => 'in', 'value' => 'foo,bar,baz'], $result->value);
    }

    public function testFormatNullReturnsNull(): void
    {
        $result = $this->formatter->format(null, $this->filter());

        self::assertNull($result->value);
    }
}