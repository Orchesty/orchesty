<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection;

use Exception;
use Hanaboso\PipesFramework\HbPFLogsBundle\HbPFLogsBundle;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class HbPFLogsExtension
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection
 * @codeCoverageIgnore
 */
class HbPFLogsExtension extends Extension implements PrependExtensionInterface
{

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return HbPFLogsBundle::KEY;
    }

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
     * @param mixed[]          $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter(HbPFLogsBundle::KEY, $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('controllers.yml');
        $loader->load('services.yml');
    }

}
