<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFPlatformServicesBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class HbPFPlatformServicesExtension
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFPlatformServicesBundle\DependencyInjection
 */
final class HbPFPlatformServicesExtension extends Extension
{

    /**
     * @param mixed[]          $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configs;

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('controllers.yaml');
        $loader->load('services.yaml');
    }

}
