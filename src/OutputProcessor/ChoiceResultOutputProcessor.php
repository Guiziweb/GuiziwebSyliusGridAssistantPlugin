<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\OutputProcessor;

use Symfony\AI\Agent\Output;
use Symfony\AI\Agent\OutputProcessorInterface;
use Symfony\AI\Platform\Result\ChoiceResult;
use Symfony\AI\Platform\Result\ToolCallResult;

/**
 * Extracts ToolCallResult from ChoiceResult before AgentProcessor runs.
 *
 * When the LLM returns both text and a tool call simultaneously, the OpenAI
 * ResultConverter wraps them in a ChoiceResult. AgentProcessor only handles
 * ToolCallResult directly, so without this processor the tool would never be called.
 */
final class ChoiceResultOutputProcessor implements OutputProcessorInterface
{
    public function processOutput(Output $output): void
    {
        $result = $output->getResult();
        if (!$result instanceof ChoiceResult) {
            return;
        }

        foreach ($result->getContent() as $choice) {
            if ($choice instanceof ToolCallResult) {
                $output->setResult($choice);

                return;
            }
        }
    }
}
