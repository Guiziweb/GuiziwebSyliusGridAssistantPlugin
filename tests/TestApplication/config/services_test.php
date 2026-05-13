<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\ClockInterface as SymfonyClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    if (str_starts_with($container->env(), 'test')) {
        $container->import('../../../vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services.xml');
        $container->import('@GuiziwebSyliusGridAssistantPlugin/tests/Behat/Resources/services.xml');

        $container->services()
            ->set(MockClock::class)
                ->args(['2026-01-01 00:00:00'])
            ->alias(ClockInterface::class, MockClock::class)
            ->alias(SymfonyClockInterface::class, MockClock::class)
        ;
    }
};