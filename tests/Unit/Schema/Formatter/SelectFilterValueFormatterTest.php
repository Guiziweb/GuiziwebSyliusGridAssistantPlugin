<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Formatter;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\SelectFilterValueFormatter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Filter;

final class SelectFilterValueFormatterTest extends TestCase
{
    private SelectFilterValueFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new SelectFilterValueFormatter();
    }

    private function filter(array $formOptions = []): Filter
    {
        $filter = Filter::fromNameAndType('state', 'select');
        $filter->setFormOptions($formOptions);

        return $filter;
    }

    public function testFormatValidChoice(): void
    {
        $filter = $this->filter(['choices' => ['New' => 'new', 'Fulfilled' => 'fulfilled']]);

        $result = $this->formatter->format('new', $filter);

        self::assertSame('new', $result->value);
    }

    public function testFormatInvalidChoiceReturnsNull(): void
    {
        $filter = $this->filter(['choices' => ['New' => 'new', 'Fulfilled' => 'fulfilled']]);

        $result = $this->formatter->format('unknown', $filter);

        self::assertNull($result->value);
    }

    public function testFormatMultipleValidChoices(): void
    {
        $filter = $this->filter([
            'choices' => ['New' => 'new', 'Fulfilled' => 'fulfilled', 'Cancelled' => 'cancelled'],
            'multiple' => true,
        ]);

        $result = $this->formatter->format(['new', 'fulfilled'], $filter);

        self::assertSame(['new', 'fulfilled'], $result->value);
    }

    public function testFormatMultipleFiltersOutInvalidChoices(): void
    {
        $filter = $this->filter([
            'choices' => ['New' => 'new', 'Fulfilled' => 'fulfilled'],
            'multiple' => true,
        ]);

        $result = $this->formatter->format(['new', 'unknown'], $filter);

        self::assertSame(['new'], $result->value);
    }

    public function testFormatMultipleAllInvalidReturnsNull(): void
    {
        $filter = $this->filter([
            'choices' => ['New' => 'new'],
            'multiple' => true,
        ]);

        $result = $this->formatter->format(['unknown'], $filter);

        self::assertNull($result->value);
    }

    public function testFormatNoChoicesPassesThrough(): void
    {
        $result = $this->formatter->format('anything', $this->filter());

        self::assertSame('anything', $result->value);
    }

    public function testFormatNullReturnsNull(): void
    {
        $filter = $this->filter(['choices' => ['New' => 'new']]);

        $result = $this->formatter->format(null, $filter);

        self::assertNull($result->value);
    }
}