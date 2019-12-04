<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFMapperBundle\DependencyInjection;

use Exception;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class HbPFMapperExtension
 *
 * @package Hanaboso\PipesPhpSdk\HbPFMapperBundle\DependencyInjection
 */
class HbPFMapperExtension extends Extension implements PrependExtensionInterface
{

    /**
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
        $loader->load('controllers.yaml');
        $loader->load('services.yaml');
    }

}
