<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserTaskBundle\DependencyInjection;

use Exception;
use Hanaboso\PipesFramework\HbPFUserBundle\DependencyInjection\Configuration;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\HbPFUserTaskBundle;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class HbPFUserTaskExtension
 *
 * @package Hanaboso\PipesFramework\HbPFUserTaskBundle\DependencyInjection
 *
 * @codeCoverageIgnore
 */
final class HbPFUserTaskExtension extends Extension implements PrependExtensionInterface
{

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return HbPFUserTaskBundle::KEY;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('hb_pf_commons')) {
            throw new RuntimeException('You must register HbPFCommonsBundle before.');
        }
        if (!$container->hasExtension('rabbit_mq')) {
            throw new RuntimeException('You must register HbPFRabbitMqBundle before.');
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/prepend-config'));
        $loader->load('rabbitmq.yaml');
    }

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
        $loader->load('handlers.yaml');
        $loader->load('services.yaml');
    }

}
