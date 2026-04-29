<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Formatter;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\ExistsFilterValueFormatter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;

final class ExistsFilterValueFormatterTest extends TestCase
{
    private ExistsFilterValueFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ExistsFilterValueFormatter();
    }

    private function filter(): Filter
    {
        return Filter::fromNameAndType('hasStock', 'exists');
    }

    /**
     * Sylius ExistsFilter uses (bool) $data.
     * The formatter must return actual booleans so (bool) 'false' !== true bug is avoided.
     */
    public function testFormatBoolTrue(): void
    {
        $result = $this->formatter->format(true, $this->filter());

        self::assertTrue($result->value);
        self::assertIsBool($result->value);
        self::assertEmpty($result->warnings);
    }

    public function testFormatBoolFalse(): void
    {
        $result = $this->formatter->format(false, $this->filter());

        self::assertFalse($result->value);
        self::assertIsBool($result->value);
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