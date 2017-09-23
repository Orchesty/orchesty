<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\DependencyInjection;

use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class HbPFConfiguratorExtension
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\DependencyInjection
 */
class HbPFConfiguratorExtension extends Extension implements PrependExtensionInterface
{

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('hb_pf_commons')) {
            throw new RuntimeException('You must register HbPFCommonsBundle before.');
        };

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/prepend-config'));
        $loader->load('parameters.yml');
        $loader->load('starting-point.yml');
        $loader->load('doctrine_mongo.yml');

        $container->setParameter('src_dir', __DIR__ . '/../..');
    }

    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('handlers.yml');
        $loader->load('managers.yml');
        $loader->load('services.yml');
    }

}