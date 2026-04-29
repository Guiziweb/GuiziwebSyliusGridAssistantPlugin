<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class GuiziwebSyliusGridAssistantExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('guiziweb.grid_assistant.model', $config['model']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'rate_limiter' => [
                'guiziweb_grid_assistant' => [
                    'policy' => 'fixed_window',
                    'limit' => 10,
                    'interval' => '1 minute',
                ],
            ],
        ]);

        $container->prependExtensionConfig('twig_component', [
            'defaults' => [
                'Guiziweb\\SyliusGridAssistantPlugin\\Twig\\Component\\' => [
                    'name_prefix' => 'guiziweb_internal',
                    'template_directory' => 'components',
                ],
            ],
        ]);
    }
}
