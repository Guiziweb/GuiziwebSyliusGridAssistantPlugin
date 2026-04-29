<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin;

use Guiziweb\SyliusGridAssistantPlugin\DependencyInjection\Compiler\RegisterFilterSchemaBuilderPass;
use Guiziweb\SyliusGridAssistantPlugin\Schema\Builder\FilterSchemaBuilderInterface;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class GuiziwebSyliusGridAssistantPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(FilterSchemaBuilderInterface::class)
            ->addTag('guiziweb.grid_assistant.filter_schema_builder');

        $container->addCompilerPass(new RegisterFilterSchemaBuilderPass());
    }
}
