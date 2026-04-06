<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Toolbox;

use Symfony\AI\Platform\Tool\Tool;

/**
 * Interface for services that enrich tool descriptions with dynamic content.
 */
interface ToolDescriptionEnricherInterface
{
    /**
     * Check if this enricher supports the given tool reference.
     */
    public function supports(string $toolReference): bool;

    /**
     * Enrich the tool description with dynamic content.
     */
    public function enrich(Tool $tool): Tool;
}
