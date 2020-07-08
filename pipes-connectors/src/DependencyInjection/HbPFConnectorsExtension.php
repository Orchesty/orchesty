<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\DependencyInjection;

use Exception;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class HbPFConnectorsExtension
 *
 * @package Hanaboso\HbPFConnectors\DependencyInjection
 * @codeCoverageIgnore
 */
class HbPFConnectorsExtension extends Extension implements PrependExtensionInterface
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
        $loader->load('services.yaml');
        $loader->load('airtable.yaml');
        $loader->load('amazon.yaml');
        $loader->load('bigcommerce.yaml');
        $loader->load('googledrive.yaml');
        $loader->load('hubspot.yaml');
        $loader->load('mailchimp.yaml');
        $loader->load('nutshell.yaml');
        $loader->load('pipedrive.yaml');
        $loader->load('quickbooks.yaml');
        $loader->load('salesforce.yaml');
        $loader->load('sendgrid.yaml');
        $loader->load('shipstation.yaml');
        $loader->load('shopify.yaml');
        $loader->load('shoptet.yaml');
        $loader->load('wisepops.yaml');
        $loader->load('zendesk.yaml');
        $loader->load('zoho.yaml');
    }

}
