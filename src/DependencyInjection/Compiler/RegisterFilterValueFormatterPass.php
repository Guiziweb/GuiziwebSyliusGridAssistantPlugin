<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\DependencyInjection\Compiler;

use Guiziweb\SyliusGridAssistantPlugin\Schema\Formatter\FilterValueFormatterRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterFilterValueFormatterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(FilterValueFormatterRegistry::class)) {
            return;
        }

        $registry = $container->findDefinition(FilterValueFormatterRegistry::class);

        foreach ($container->findTaggedServiceIds('guiziweb.grid_assistant.filter_value_formatter') as $id => $tags) {
            $registry->addMethodCall('register', [new Reference($id)]);
        }
    }
}
