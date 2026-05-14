<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\Processor;

use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessor;
use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessorException;
use Guiziweb\SyliusGridAssistantPlugin\RateLimiter\RateLimitKeyResolverInterface;
use Guiziweb\SyliusGridAssistantPlugin\Resolver\GridQueryResolverException;
use Guiziweb\SyliusGridAssistantPlugin\Resolver\GridQueryResolverInterface;
use Guiziweb\SyliusGridAssistantPlugin\Schema\GridSchemaBuilderInterface;
use Guiziweb\SyliusGridAssistantPlugin\Validator\GridCriteriaValidatorInterface;
use Guiziweb\SyliusGridAssistantPlugin\Validator\GridSortingValidatorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GridQueryProcessorTest extends TestCase
{
    public function testThrowsWhenKeyResolverRejectsTheCaller(): void
    {
        $keyResolver = $this->createMock(RateLimitKeyResolverInterface::class);
        $keyResolver->method('resolve')
            ->willThrowException(new GridQueryProcessorException('rate_limit_unauthenticated'));

        $processor = $this->makeProcessor(keyResolver: $keyResolver);

        $this->expectException(GridQueryProcessorException::class);
        $this->expectExceptionMessage('rate_limit_unauthenticated');

        $processor->process('any query', 'sylius_admin_order', 'sylius_admin_order_index', []);
    }

    public function testKeyReturnedByResolverIsUsedAsRateLimiterBucket(): void
    {
        $keyResolver = $this->createMock(RateLimitKeyResolverInterface::class);
        $keyResolver->method('resolve')->willReturn('192.0.2.42');

        $rateLimit = new RateLimit(10, new \DateTimeImmutable('+1 minute'), true, 10);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($rateLimit);

        $factory = $this->createMock(RateLimiterFactoryInterface::class);
        $factory->expects(self::once())
            ->method('create')
            ->with('192.0.2.42')
            ->willReturn($limiter);

        $schemaBuilder = $this->createMock(GridSchemaBuilderInterface::class);
        $schemaBuilder->method('gridExists')->willReturn(false);

        $processor = $this->makeProcessor(
            schemaBuilder: $schemaBuilder,
            rateLimiterFactory: $factory,
            keyResolver: $keyResolver,
        );

        $this->expectException(GridQueryProcessorException::class);
        $processor->process('any query', 'unknown_grid', 'sylius_admin_order_index', []);
    }

    public function testThrowsWhenRateLimitIsExceeded(): void
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

        $this->expectException(GridQueryProcessorException::class);
        $this->expectExceptionMessageMatches('/Rate limit exceeded.+seconds/');

        $processor->process('any query', 'sylius_admin_order', 'sylius_admin_order_index', []);
    }

    public function testThrowsTranslatedExceptionWhenResolverFails(): void
    {
        $resolver = $this->createMock(GridQueryResolverInterface::class);
        $resolver->method('resolve')->willThrowException(new GridQueryResolverException('LLM down'));

        $schemaBuilder = $this->createMock(GridSchemaBuilderInterface::class);
        $schemaBuilder->method('gridExists')->willReturn(true);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id) => 'guiziweb.grid_assistant.ai_processing_failed' === $id
                ? 'AI processing failed. Please try again.'
                : $id,
        );

        $processor = $this->makeProcessor(
            queryResolver: $resolver,
            schemaBuilder: $schemaBuilder,
            translator: $translator,
        );

        $this->expectException(GridQueryProcessorException::class);
        $this->expectExceptionMessage('AI processing failed. Please try again.');

        $processor->process('any query', 'sylius_admin_order', 'sylius_admin_order_index', []);
    }

    public function testThrowsTranslatedExceptionWhenGridDoesNotExist(): void
    {
        $schemaBuilder = $this->createMock(GridSchemaBuilderInterface::class);
        $schemaBuilder->method('gridExists')->willReturn(false);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $params = []) => 'guiziweb.grid_assistant.grid_not_found' === $id
                ? sprintf('Grid "%s" not found.', $params['%grid_code%'] ?? '')
                : $id,
        );

        $processor = $this->makeProcessor(schemaBuilder: $schemaBuilder, translator: $translator);

        $this->expectException(GridQueryProcessorException::class);
        $this->expectExceptionMessage('Grid "unknown_grid" not found.');

        $processor->process('any query', 'unknown_grid', 'sylius_admin_order_index', []);
    }

    private function makeProcessor(
        ?GridQueryResolverInterface $queryResolver = null,
        ?GridCriteriaValidatorInterface $criteriaValidator = null,
        ?GridSortingValidatorInterface $sortingValidator = null,
        ?GridSchemaBuilderInterface $schemaBuilder = null,
        ?RateLimiterFactoryInterface $rateLimiterFactory = null,
        ?RateLimitKeyResolverInterface $keyResolver = null,
        ?TranslatorInterface $translator = null,
    ): GridQueryProcessor {
        if (null === $keyResolver) {
            $keyResolver = $this->createMock(RateLimitKeyResolverInterface::class);
            $keyResolver->method('resolve')->willReturn('admin@example.com');
        }

        if (null === $rateLimiterFactory) {
            $rateLimit = new RateLimit(10, new \DateTimeImmutable('+1 minute'), true, 10);
            $limiter = $this->createMock(LimiterInterface::class);
            $limiter->method('consume')->willReturn($rateLimit);

            $rateLimiterFactory = $this->createMock(RateLimiterFactoryInterface::class);
            $rateLimiterFactory->method('create')->willReturn($limiter);
        }

        return new GridQueryProcessor(
            $queryResolver ?? $this->createMock(GridQueryResolverInterface::class),
            $criteriaValidator ?? $this->createMock(GridCriteriaValidatorInterface::class),
            $sortingValidator ?? $this->createMock(GridSortingValidatorInterface::class),
            $schemaBuilder ?? $this->createMock(GridSchemaBuilderInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(LoggerInterface::class),
            $rateLimiterFactory,
            $keyResolver,
            $translator ?? $this->createMock(TranslatorInterface::class),
            $this->createMock(GridProviderInterface::class),
        );
    }
}