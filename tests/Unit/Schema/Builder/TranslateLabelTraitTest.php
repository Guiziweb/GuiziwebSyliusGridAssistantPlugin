<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Schema\Builder;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\TranslateLabelTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslateLabelTraitTest extends TestCase
{
    public function testTranslatesStringLabel(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())->method('trans')->with('order.label')->willReturn('Order');

        self::assertSame('Order', $this->subject($translator)->expose('order.label'));
    }

    /**
     * @dataProvider nonStringLabels
     */
    public function testReturnsEmptyForNonStringLabel(string|bool|null $label): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())->method('trans');

        self::assertSame('', $this->subject($translator)->expose($label));
    }

    /**
     * @return iterable<string, array{string|bool|null}>
     */
    public static function nonStringLabels(): iterable
    {
        yield 'null' => [null];
        yield 'true' => [true];
        yield 'false' => [false];
    }

    private function subject(TranslatorInterface $translator): object
    {
        return new class($translator) {
            use TranslateLabelTrait;

            public function __construct(private readonly TranslatorInterface $translator)
            {
            }

            public function expose(string|bool|null $label): string
            {
                return $this->translateLabel($label);
            }
        };
    }
}