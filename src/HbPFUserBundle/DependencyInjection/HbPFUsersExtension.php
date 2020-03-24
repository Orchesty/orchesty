<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class HbPFUsersExtension
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\DependencyInjection
 *
 * @codeCoverageIgnore
 */
class HbPFUsersExtension extends Extension
{

    /**
     * @param mixed[]          $configs
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
