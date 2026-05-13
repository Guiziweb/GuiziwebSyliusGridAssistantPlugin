<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GridAssistantExtension extends AbstractExtension
{
    /**
     * @param list<string> $enabledGrids
     */
    public function __construct(
        private readonly array $enabledGrids,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('guiziweb_grid_assistant_enabled', $this->isEnabled(...)),
        ];
    }

    public function isEnabled(string $gridCode): bool
    {
        return in_array($gridCode, $this->enabledGrids, true);
    }
}
