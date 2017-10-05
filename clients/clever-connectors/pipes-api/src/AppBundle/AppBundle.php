<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle;

use CleverConnectors\AppBundle\Model\Systems\SystemCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class AppBundle
 *
 * @package CleverConnectors\AppBundle
 */
class AppBundle extends Bundle
{

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        (new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config')))->load('parameters.yml');
        (new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config')))->load('dev_services.yml');
        $container->addCompilerPass(new SystemCompilerPass());
    }

}