<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\DependencyInjection;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class HbPFUsageStatsExtension
 *
 * @package Hanaboso\PipesFramework\HbPFUsageStatsBundle\DependencyInjection
 */
final class HbPFUsageStatsExtension extends Extension
{

    /**
     * Loads a specific configuration.
     *
     * @param mixed[]          $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws Exception
     * @throws InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('services.yml');
        $loader->load('parameters.yml');
    }

}
