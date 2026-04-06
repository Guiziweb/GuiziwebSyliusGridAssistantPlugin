<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\DependencyInjection\Compiler;

use Guiziweb\SyliusGridAssistantPlugin\Schema\FilterSchemaBuilderRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterFilterSchemaBuilderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(FilterSchemaBuilderRegistry::class)) {
            return;
        }

        $registry = $container->findDefinition(FilterSchemaBuilderRegistry::class);

        foreach ($container->findTaggedServiceIds('guiziweb.filter_schema_builder') as $id => $tags) {
            $registry->addMethodCall('register', [new Reference($id)]);
        }
    }
}
