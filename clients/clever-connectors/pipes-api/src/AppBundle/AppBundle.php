<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle;

use CleverConnectors\AppBundle\Model\Systems\SystemCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class AppBundle
 *
 * @package CleverConnectors\AppBundle
 */
class AppBundle extends Bundle
{

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('dev_services.yml');

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/system'));
        $loader->load('cleverMonitor.yml');
        $loader->load('magento2.yml');
        $loader->load('shopify.yml');
        $container->addCompilerPass(new SystemCompilerPass());
    }

}