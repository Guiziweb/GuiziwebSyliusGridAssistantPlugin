<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Processor;

use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessor;
use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\FilterValueFormatterRegistryInterface;
use Guiziweb\SyliusGridAssistantPlugin\Schema\GridSchemaBuilderInterface;
use Guiziweb\SyliusGridAssistantPlugin\Toolbox\GridToolSchemaFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GridQueryProcessorTest extends TestCase
{
    public function testReturnsErrorWhenUserIsNotAuthenticated(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('rate_limit_unauthenticated');

        $processor = $this->makeProcessor(security: $security, translator: $translator);

        $result = $processor->process('any query', 'sylius_admin_order', 'sylius_admin_order_index', []);

        self::assertSame(['error' => 'rate_limit_unauthenticated'], $result);
    }

    public function testReturnsErrorWhenRateLimitIsExceeded(): void
    {
        $rateLimit = new RateLimit(0, new \DateTimeImmutable('+42 seconds'), false, 10);

        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($rateLimit);

        $factory = $this->createMock(RateLimiterFactoryInterface::class);
        $factory->method('create')->willReturn($limiter);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $params = []) => 'guiziweb.grid_assistant.rate_limit_exceeded' === $id
                ? sprintf('Rate limit exceeded, retry in %d seconds', $params['%seconds%'] ?? 0)
                : $id,
        );

        $processor = $this->makeProcessor(rateLimiterFactory: $factory, translator: $translator);

        $result = $processor->process('any query', 'sylius_admin_order', 'sylius_admin_order_index', []);

        self::assertArrayHasKey('error', $result);
        self::assertStringContainsString('Rate limit exceeded', $result['error']);
        self::assertStringContainsString('seconds', $result['error']);
    }

    public function testReturnsErrorWhenPlatformInvokeThrows(): void
    {
        $platform = $this->createMock(PlatformInterface::class);
        $platform->method('invoke')->willThrowException(new \RuntimeException('LLM down'));

        $schemaBuilder = $this->createMock(GridSchemaBuilderInterface::class);
        $schemaBuilder->method('gridExists')->willReturn(true);
        $schemaBuilder->method('buildSchema')->willReturn([
            'grid_code' => 'sylius_admin_order',
            'entity_class' => null,
            'filters' => [],
            'sortable_fields' => [],
            'default_sorting' => [],
        ]);

        $schemaFactory = $this->createMock(GridToolSchemaFactoryInterface::class);
        $schemaFactory->method('buildParameters')->willReturn([
            'type' => 'object',
            'properties' => [],
            'required' => [],
            'additionalProperties' => false,
        ]);

        $processor = $this->makeProcessor(
            platform: $platform,
            schemaBuilder: $schemaBuilder,
            schemaFactory: $schemaFactory,
        );

        $result = $processor->process('any query', 'sylius_admin_order', 'sylius_admin_order_index', []);

        self::assertArrayHasKey('error', $result);
        self::assertStringContainsString('AI processing failed', $result['error']);
        self::assertStringContainsString('LLM down', $result['error']);
    }

    private function makeProcessor(
        ?PlatformInterface $platform = null,
        ?GridSchemaBuilderInterface $schemaBuilder = null,
        ?GridToolSchemaFactoryInterface $schemaFactory = null,
        ?RateLimiterFactoryInterface $rateLimiterFactory = null,
        ?Security $security = null,
        ?TranslatorInterface $translator = null,
    ): GridQueryProcessor {
        if (null === $security) {
            $user = $this->createMock(UserInterface::class);
            $user->method('getUserIdentifier')->willReturn('admin@example.com');

            $security = $this->createMock(Security::class);
            $security->method('getUser')->willReturn($user);
        }

        if (null === $rateLimiterFactory) {
            $rateLimit = new RateLimit(10, new \DateTimeImmutable('+1 minute'), true, 10);
            $limiter = $this->createMock(LimiterInterface::class);
            $limiter->method('consume')->willReturn($rateLimit);

            $rateLimiterFactory = $this->createMock(RateLimiterFactoryInterface::class);
            $rateLimiterFactory->method('create')->willReturn($limiter);
        }

        return new GridQueryProcessor(
            $platform ?? $this->createMock(PlatformInterface::class),
            'gpt-4o',
            $schemaBuilder ?? $this->createMock(GridSchemaBuilderInterface::class),
            $schemaFactory ?? $this->createMock(GridToolSchemaFactoryInterface::class),
            $this->createMock(FilterValueFormatterRegistryInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(LoggerInterface::class),
            $rateLimiterFactory,
            $security,
            $translator ?? $this->createMock(TranslatorInterface::class),
            $this->createMock(ClockInterface::class),
            $this->createMock(GridProviderInterface::class),
        );
    }
}