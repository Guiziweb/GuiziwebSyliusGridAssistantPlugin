<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Toolbox;

use Symfony\AI\Agent\Toolbox\ToolFactoryInterface;

/**
 * Decorates the default ToolFactory to inject dynamic content into tool descriptions.
 * Uses tagged enrichers to allow dynamic schema generation.
 */
final class DynamicDescriptionToolFactory implements ToolFactoryInterface
{
    /**
     * @param iterable<ToolDescriptionEnricherInterface> $enrichers
     */
    public function __construct(
        private readonly ToolFactoryInterface $decorated,
        private readonly iterable $enrichers = [],
    ) {
    }

    public function getTool(string $reference): iterable
    {
        foreach ($this->decorated->getTool($reference) as $tool) {
            foreach ($this->enrichers as $enricher) {
                if ($enricher->supports($reference)) {
                    $tool = $enricher->enrich($tool);
                }
            }

            yield $tool;
        }
    }
}