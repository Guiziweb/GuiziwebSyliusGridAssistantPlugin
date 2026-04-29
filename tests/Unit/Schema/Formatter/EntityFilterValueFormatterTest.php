<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Formatter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\EntityFilterValueFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\UX\Autocomplete\AutocompleterRegistry;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

final class EntityFilterValueFormatterTest extends TestCase
{
    private EntityFilterValueFormatter $formatter;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    private AutocompleterRegistry $autocompleterRegistry;

    /** @var EntityAutocompleterInterface&MockObject */
    private EntityAutocompleterInterface $autocompleter;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->autocompleter = $this->createMock(EntityAutocompleterInterface::class);
        $this->autocompleterRegistry = new AutocompleterRegistry(new ServiceLocator([
            'sylius_admin_grid_filter_autocomplete' => fn () => $this->autocompleter,
            'sylius_admin_grid_filter_translatable_autocomplete' => fn () => $this->autocompleter,
        ]));

        $this->formatter = new EntityFilterValueFormatter(
            $this->entityManager,
            $this->autocompleterRegistry,
            new NullLogger(),
        );
    }

    private function filter(string $type = 'ux_autocomplete', array $formOptions = []): Filter
    {
        $filter = Filter::fromNameAndType('customer', $type);
        $filter->setFormOptions($formOptions);

        return $filter;
    }

    public function testFormatIntegerIdPassthrough(): void
    {
        $result = $this->formatter->format(42, $this->filter());

        self::assertSame(42, $result->value);
        self::assertEmpty($result->warnings);
    }

    public function testFormatNumericStringIdPassthrough(): void
    {
        $result = $this->formatter->format('123', $this->filter());

        self::assertSame(123, $result->value);
    }

    public function testFormatNullReturnsNull(): void
    {
        $result = $this->formatter->format(null, $this->filter());

        self::assertNull($result->value);
    }

    public function testFormatEmptyStringReturnsNull(): void
    {
        $result = $this->formatter->format('', $this->filter());

        self::assertNull($result->value);
    }

    public function testFormatStringSearchFindsEntity(): void
    {
        $entity = new \stdClass();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn([$entity]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);

        $this->autocompleter->method('getEntityClass')->willReturn(\stdClass::class);
        $this->autocompleter->method('createFilteredQueryBuilder')->willReturn($qb);
        $this->autocompleter->method('getValue')->willReturn('7');

        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->formatter->format('John Doe', $this->filter());

        self::assertSame(7, $result->value);
        self::assertEmpty($result->warnings);
    }

    public function testFormatStringSearchEntityNotFoundReturnsWarning(): void
    {
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);

        $this->autocompleter->method('getEntityClass')->willReturn(\stdClass::class);
        $this->autocompleter->method('createFilteredQueryBuilder')->willReturn($qb);

        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->formatter->format('Unknown', $this->filter());

        self::assertNull($result->value);
        self::assertNotEmpty($result->warnings);
        self::assertStringContainsString('Unknown', $result->warnings[0]);
    }

    public function testFormatMultipleIds(): void
    {
        $result = $this->formatter->format([1, 2, 3], $this->filter('ux_autocomplete', ['multiple' => true]));

        self::assertSame([1, 2, 3], $result->value);
    }
}