<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFApplicationBundle\DependencyInjection;

use Exception;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class HbPFApplicationExtension
 *
 * @package Hanaboso\PipesPhpSdk\HbPFApplicationBundle\DependencyInjection
 */
class HbPFApplicationExtension extends Extension implements PrependExtensionInterface
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
        }
    }

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('oauth_providers.yaml');
        $loader->load('controller.yaml');
        $loader->load('manager.yaml');
        $loader->load('handler.yaml');
    }

}
