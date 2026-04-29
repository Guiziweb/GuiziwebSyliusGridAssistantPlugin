<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('guiziweb_sylius_grid_assistant');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('model')
                    ->defaultValue('gpt-4o')
                    ->cannotBeEmpty()
                    ->info('The LLM model name to use. Must match the platform configured in symfony/ai-bundle (e.g. "gpt-4o", "claude-sonnet-4-6").')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
