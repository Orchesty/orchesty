<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\DependencyInjection;

use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AppExtension extends Extension implements PrependExtensionInterface
{

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('rabbit-mq')) {
            throw new RuntimeException('You must register HbPFRabbitMqBundle before.');
        };

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/prepend-config'));
        $loader->load('rabbit_mq.yml');
    }


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
        $loader->load('commands.yml');
        $loader->load('dev_services.yml');
        $loader->load('listeners.yml');
        $loader->load('custom_nodes.yml');
        $loader->load('services.yml');
        $loader->load('webhook.yml');

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/system'));
        $loader->load('cleverMonitor.yml');
        $loader->load('magento2.yml');
        $loader->load('shopify.yml');
        $loader->load('salesforce.yml');
        $loader->load('wisepops.yml');
        $loader->load('bigcommerce.yml');
        $loader->load('shipstation.yml');
        $loader->load('hubspot.yml');
        $loader->load('nutshell.yml');
        $loader->load('pipedrive.yml');
        $loader->load('zendesk.yml');
        $loader->load('basecrm.yml');
        $loader->load('mailmunch.yml');
        $loader->load('zoho.yml');
        $loader->load('quickbooks.yml');
        $loader->load('zapier.yml');
        $loader->load('prestashop.yml');
        $loader->load('plugins.yml');
    }

}
