<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\DependencyInjection;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class HbPFLongRunningNodeExtension
 *
 * @package Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\DependencyInjection
 */
class HbPFLongRunningNodeExtension extends Extension implements PrependExtensionInterface
{

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('hb_pf_commons')) {
            throw new RuntimeException('You must register HbPFCommonsBundle before.');
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/prepend-config'));
        $loader->load('rabbitmq.yml');

        $container->setParameter('src_dir', __DIR__ . '/../..');
    }

    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws InvalidArgumentException When provided tag is not defined in this extension
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('controllers.yml');
    }

}