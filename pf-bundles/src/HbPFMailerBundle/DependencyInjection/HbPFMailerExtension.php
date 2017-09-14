<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMailerBundle\DependencyInjection;

use RuntimeException;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class HbPFMailerExtension extends Extension implements PrependExtensionInterface
{

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'hb_pf_mailer';
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('hb_pf_commons')) {
            throw new RuntimeException('You must register HbPFCommonsBundle before.');
        } elseif (!$container->hasExtension('rabbit-mq')) {
            throw new RuntimeException('You must register RabbitMqBundle before.');
        };

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/prepend-config'));
        $loader->load('hb_pf_mailer.yml');
    }

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $container->setParameter($this->getAlias(), $this->processConfiguration($configuration, $configs));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('controllers.yml');
        $loader->load('handlers.yml');
        $loader->load('message_builders.yml');
        $loader->load('services.yml');
        $loader->load('transports.yml');
    }

}
