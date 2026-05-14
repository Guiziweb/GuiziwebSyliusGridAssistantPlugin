<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\RateLimiter;

use Guiziweb\SyliusGridAssistantPlugin\Processor\GridQueryProcessorException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class UserIdentifierKeyResolver implements RateLimitKeyResolverInterface
{
    public function __construct(
        private Security $security,
        private TranslatorInterface $translator,
    ) {
    }

    public function resolve(): string
    {
        $user = $this->security->getUser();
        if (null === $user) {
            throw new GridQueryProcessorException($this->translator->trans('guiziweb.grid_assistant.rate_limit_unauthenticated'));
        }

        return $user->getUserIdentifier();
    }
}
