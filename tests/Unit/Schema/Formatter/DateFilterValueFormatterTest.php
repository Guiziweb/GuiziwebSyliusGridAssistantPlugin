<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Formatter;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\DateFilterValueFormatter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;

final class DateFilterValueFormatterTest extends TestCase
{
    private DateFilterValueFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new DateFilterValueFormatter();
    }

    private function filter(): Filter
    {
        return Filter::fromNameAndType('createdAt', 'date');
    }

    public function testFormatStartAndEnd(): void
    {
        $result = $this->formatter->format(['start' => '2024-01-01', 'end' => '2024-12-31'], $this->filter());

        self::assertSame([
            'from' => ['date' => '2024-01-01'],
            'to' => ['date' => '2024-12-31'],
        ], $result->value);
    }

    public function testFormatStartOnly(): void
    {
        $result = $this->formatter->format(['start' => '2024-01-01'], $this->filter());

        self::assertSame(['from' => ['date' => '2024-01-01']], $result->value);
    }

    public function testFormatEndOnly(): void
    {
        $result = $this->formatter->format(['end' => '2024-12-31'], $this->filter());

        self::assertSame(['to' => ['date' => '2024-12-31']], $result->value);
    }

    public function testFormatEmptyArrayReturnsNull(): void
    {
        $result = $this->formatter->format([], $this->filter());

        self::assertNull($result->value);
    }

    public function testFormatNonArrayReturnsNull(): void
    {
        $result = $this->formatter->format('2024-01-01', $this->filter());

        self::assertNull($result->value);
    }
}