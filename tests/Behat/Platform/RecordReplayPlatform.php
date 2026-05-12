<?php

declare(strict_types=1);

namespace Tests\Guiziweb\SyliusGridAssistantPlugin\Behat\Platform;

use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\MessageInterface;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use Symfony\AI\Platform\PlainConverter;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Result\InMemoryRawResult;
use Symfony\AI\Platform\Result\ObjectResult;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\TextResult;

final class RecordReplayPlatform implements PlatformInterface
{
    public function __construct(
        private readonly PlatformInterface $real,
        private readonly string $fixturesDir,
        private readonly bool $recordMode = false,
    ) {
    }

    public function invoke(string $model, array|string|object $input, array $options = []): DeferredResult
    {
        $fixturePath = sprintf('%s/%s/%s.json', $this->fixturesDir, $model, $this->hashRequest($model, $input, $options));

        if (!$this->recordMode && file_exists($fixturePath)) {
            return $this->replay($fixturePath, $options);
        }

        $result = $this->real->invoke($model, $input, $options)->getResult();
        $this->record($fixturePath, $result);

        return $this->wrap($result, $options);
    }

    public function getModelCatalog(): ModelCatalogInterface
    {
        return $this->real->getModelCatalog();
    }

    /**
     * @param array<string, mixed> $options
     */
    private function hashRequest(string $model, array|string|object $input, array $options): string
    {
        return md5(json_encode([
            'model' => $model,
            'input' => $this->normalizeInput($input),
            'options' => $options,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * MessageBag carries a time-based UUID per instance, so we extract only the stable parts
     * (role + content) to keep the hash reproducible across runs.
     *
     * @return array<int, array{role: string, content: mixed}>|string|array<mixed>
     */
    private function normalizeInput(array|string|object $input): array|string
    {
        if (is_string($input) || is_array($input)) {
            return $input;
        }

        if ($input instanceof MessageBag) {
            return array_map(
                static fn (MessageInterface $msg) => [
                    'role' => $msg->getRole()->value,
                    'content' => $msg instanceof UserMessage ? $msg->asText() : self::stringify($msg->getContent()),
                ],
                $input->getMessages(),
            );
        }

        return ['__class' => $input::class, '__serialized' => md5(serialize($input))];
    }

    private static function stringify(mixed $value): mixed
    {
        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return $value;
    }

    private function record(string $path, ResultInterface $result): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $payload = [
            'type' => $result instanceof ObjectResult ? 'object' : 'text',
            'content' => $result->getContent(),
        ];

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function replay(string $path, array $options): DeferredResult
    {
        /** @var array{type: string, content: mixed} $payload */
        $payload = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $content = $payload['content'];
        $result = 'object' === $payload['type']
            ? new ObjectResult(is_array($content) ? $content : (array) $content)
            : new TextResult(is_string($content) ? $content : json_encode($content, JSON_THROW_ON_ERROR));

        return $this->wrap($result, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function wrap(ResultInterface $result, array $options): DeferredResult
    {
        $content = $result->getContent();
        $rawResult = $result->getRawResult() ?? new InMemoryRawResult(
            is_array($content) ? $content : ['content' => $content],
            [],
            (object) (is_array($content) ? $content : ['content' => $content]),
        );

        return new DeferredResult(new PlainConverter($result), $rawResult, $options);
    }
}