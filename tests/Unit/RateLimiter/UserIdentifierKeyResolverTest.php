<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Tests\Unit\RateLimiter;

use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessorException;
use Guiziweb\SyliusGridAssistantPlugin\RateLimiter\UserIdentifierKeyResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserIdentifierKeyResolverTest extends TestCase
{
    public function testReturnsUserIdentifierWhenAuthenticated(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('admin@example.com');

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $resolver = new UserIdentifierKeyResolver(
            $security,
            $this->createMock(TranslatorInterface::class),
        );

        self::assertSame('admin@example.com', $resolver->resolve());
    }

    public function testThrowsWhenNotAuthenticated(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('rate_limit_unauthenticated');

        $resolver = new UserIdentifierKeyResolver($security, $translator);

        $this->expectException(GridQueryProcessorException::class);
        $this->expectExceptionMessage('rate_limit_unauthenticated');

        $resolver->resolve();
    }
}