<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AppExtension extends Extension
{

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('dev_services.yml');
        $loader->load('listeners.yml');
        $loader->load('custom_nodes.yml');
        $loader->load('services.yml');

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/system'));
        $loader->load('cleverMonitor.yml');
        $loader->load('magento2.yml');
        $loader->load('shopify.yml');
        $loader->load('salesforce.yml');
        $loader->load('wisepops.yml');
        $loader->load('bigcommerce.yml');
        $loader->load('shipstation.yml');
        $loader->load('pipedrive.yml');
        $loader->load('zendesk.yml');
        $loader->load('basecrm.yml');
    }

}
