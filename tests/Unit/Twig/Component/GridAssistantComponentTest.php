<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Twig\Component;

use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessorInterface;
use Guiziweb\SyliusGridAssistantPlugin\Twig\Component\GridAssistantComponent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GridAssistantComponentTest extends TestCase
{
    public function testRejectsBlankQueryWithoutCallingProcessor(): void
    {
        $processor = $this->createMock(GridQueryProcessorInterface::class);
        $processor->expects(self::never())->method('process');

        $component = $this->makeComponent($processor);
        $component->query = '';

        $result = $component->search();

        self::assertNull($result);
        self::assertSame('guiziweb.grid_assistant.query_not_blank', $component->error);
    }

    public function testRejectsQueryLongerThan500CharactersWithoutCallingProcessor(): void
    {
        $processor = $this->createMock(GridQueryProcessorInterface::class);
        $processor->expects(self::never())->method('process');

        $component = $this->makeComponent($processor);
        $component->query = str_repeat('a', 501);

        $result = $component->search();

        self::assertNull($result);
        self::assertSame('guiziweb.grid_assistant.query_too_long', $component->error);
    }

    public function testAcceptsQueryAtTheLimit(): void
    {
        $processor = $this->createMock(GridQueryProcessorInterface::class);
        $processor->expects(self::once())
            ->method('process')
            ->willReturn('/admin/orders/?criteria[state]=new');

        $component = $this->makeComponent($processor);
        $component->query = str_repeat('a', 500);
        $component->gridCode = 'sylius_admin_order';
        $component->routeName = 'sylius_admin_order_index';

        $result = $component->search();

        self::assertNotNull($result);
        self::assertNull($component->error);
    }

    private function makeComponent(GridQueryProcessorInterface $processor): GridAssistantComponent
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => $id);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return new GridAssistantComponent($processor, $translator, $validator);
    }
}
