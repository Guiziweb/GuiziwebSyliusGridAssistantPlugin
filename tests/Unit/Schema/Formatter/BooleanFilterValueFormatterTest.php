<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Formatter;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\BooleanFilterValueFormatter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;

final class BooleanFilterValueFormatterTest extends TestCase
{
    private BooleanFilterValueFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new BooleanFilterValueFormatter();
    }

    private function filter(): Filter
    {
        return Filter::fromNameAndType('enabled', 'boolean');
    }

    public function testFormatBoolTrue(): void
    {
        $result = $this->formatter->format(true, $this->filter());

        self::assertSame('true', $result->value);
        self::assertEmpty($result->warnings);
    }

    public function testFormatBoolFalse(): void
    {
        $result = $this->formatter->format(false, $this->filter());

        self::assertSame('false', $result->value);
    }

    public function testFormatStringReturnsNull(): void
    {
        $result = $this->formatter->format('true', $this->filter());

        self::assertNull($result->value);
    }

    public function testFormatNullReturnsNull(): void
    {
        $result = $this->formatter->format(null, $this->filter());

        self::assertNull($result->value);
    }
}