<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Validator;

use Guiziweb\SyliusGridAssistantPlugin\Validator\GridSortingValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Definition\Field;
use Sylius\Component\Grid\Definition\Grid;

final class GridSortingValidatorTest extends TestCase
{
    public function testKeepsValidAscDirection(): void
    {
        $validator = new GridSortingValidator($this->createMock(LoggerInterface::class));

        self::assertSame(
            ['total' => 'asc'],
            $validator->validate(['total' => 'asc'], $this->gridWithSortableField('total')),
        );
    }

    public function testNormalizesUppercaseDirectionToLowercase(): void
    {
        $validator = new GridSortingValidator($this->createMock(LoggerInterface::class));

        self::assertSame(
            ['total' => 'desc'],
            $validator->validate(['total' => 'DESC'], $this->gridWithSortableField('total')),
        );
    }

    public function testTrimsWhitespaceAroundDirection(): void
    {
        $validator = new GridSortingValidator($this->createMock(LoggerInterface::class));

        self::assertSame(
            ['total' => 'asc'],
            $validator->validate(['total' => ' asc '], $this->gridWithSortableField('total')),
        );
    }

    public function testSkipsNullDirection(): void
    {
        $validator = new GridSortingValidator($this->createMock(LoggerInterface::class));

        self::assertSame(
            [],
            $validator->validate(['total' => null], $this->gridWithSortableField('total')),
        );
    }

    public function testSkipsNonStringDirection(): void
    {
        $validator = new GridSortingValidator($this->createMock(LoggerInterface::class));

        self::assertSame(
            [],
            $validator->validate(['total' => 42], $this->gridWithSortableField('total')),
        );
    }

    public function testSkipsUnknownSortableFieldAndLogsWarning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with('[GridAssistant] Unknown sortable field skipped', ['field' => 'unknown']);

        $validator = new GridSortingValidator($logger);

        self::assertSame(
            [],
            $validator->validate(['unknown' => 'asc'], $this->gridWithSortableField('total')),
        );
    }

    public function testSkipsNonSortableField(): void
    {
        $grid = Grid::fromCodeAndDriverConfiguration('test_grid', 'doctrine/orm', []);
        $field = Field::fromNameAndType('total', 'string');
        // sortable not set -> isSortable() returns false
        $grid->addField($field);

        $validator = new GridSortingValidator($this->createMock(LoggerInterface::class));

        self::assertSame([], $validator->validate(['total' => 'asc'], $grid));
    }

    public function testInvalidDirectionIsCoercedToAsc(): void
    {
        $validator = new GridSortingValidator($this->createMock(LoggerInterface::class));

        // Current behavior: invalid direction silently becomes 'asc'.
        // Tracked for review (see PR fix/sorting-validator-strict-direction which switches to skip).
        self::assertSame(
            ['total' => 'asc'],
            $validator->validate(['total' => 'sideways'], $this->gridWithSortableField('total')),
        );
    }

    private function gridWithSortableField(string $name): Grid
    {
        $grid = Grid::fromCodeAndDriverConfiguration('test_grid', 'doctrine/orm', []);
        $field = Field::fromNameAndType($name, 'string');
        $field->setSortable($name);
        $grid->addField($field);

        return $grid;
    }
}
