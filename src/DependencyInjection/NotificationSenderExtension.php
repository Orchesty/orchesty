<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class NotificationSenderExtension
 *
 * @package Hanaboso\NotificationSender\DependencyInjection
 *
 * @codeCoverageIgnore
 */
final class NotificationSenderExtension extends Extension
{

    /**
     * @param mixed[]          $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('callbacks.yaml');
        $loader->load('controllers.yaml');
        $loader->load('handlers.yaml');
        $loader->load('managers.yaml');
        $loader->load('senders.yaml');
    }

}
