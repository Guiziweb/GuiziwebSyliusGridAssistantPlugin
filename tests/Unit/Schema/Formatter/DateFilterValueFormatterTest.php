<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Formatter;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\DateFilterValueFormatter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Filter\DateFilter;

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

    public function testGetType(): void
    {
        self::assertSame(DateFilter::NAME, DateFilterValueFormatter::getType());
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

    /**
     * @dataProvider datesMysqlRejects
     */
    public function testFormatDropsADateMysqlCannotConvert(string $date): void
    {
        $result = $this->formatter->format(['start' => $date], $this->filter());

        self::assertNull($result->value);
    }

    public static function datesMysqlRejects(): iterable
    {
        yield 'impossible month and day' => ['2025-13-45'];
        yield 'day outside the month' => ['2025-02-30'];
        yield 'relative expression' => ['last monday'];
        yield 'non iso order' => ['15/01/2025'];
        yield 'empty' => [''];
    }

    /**
     * @dataProvider datesMysqlAccepts
     */
    public function testFormatKeepsADateMysqlCanConvert(string $date): void
    {
        $result = $this->formatter->format(['start' => $date], $this->filter());

        self::assertSame(['from' => ['date' => $date]], $result->value);
    }

    public static function datesMysqlAccepts(): iterable
    {
        yield 'canonical' => ['2025-01-15'];
        yield 'without zero padding' => ['2025-1-15'];
        yield 'iso datetime suffix' => ['2025-01-15T00:00:00Z'];
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