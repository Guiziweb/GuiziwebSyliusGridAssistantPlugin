<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\EventListener;

use Guiziweb\SyliusGridAssistantPlugin\Context\GridContext;
use Symfony\AI\Agent\Toolbox\Event\ToolCallsExecuted;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class StopAfterToolCallListener
{
    public function __construct(
        private readonly GridContext $gridContext,
    ) {
    }

    public function __invoke(ToolCallsExecuted $event): void
    {
        if ($this->gridContext->hasContext()) {
            $event->setResult(new TextResult(''));
        }
    }
}
