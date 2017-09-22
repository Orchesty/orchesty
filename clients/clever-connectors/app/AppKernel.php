<?php

use CleverConnectors\AppBundle\AppBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use FOS\RestBundle\FOSRestBundle;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\HbPFApiGatewayBundle;
use Hanaboso\PipesFramework\HbPFCommonsBundle\HbPFCommonsBundle;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\HbPFConfiguratorBundle;
use Hanaboso\PipesFramework\HbPFMailerBundle\HbPFMailerBundle;
use Hanaboso\PipesFramework\RabbitMqBundle\RabbitMqBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class AppKernel
 */
class AppKernel extends Kernel
{

    /**
     * @return array
     */
    public function registerBundles()
    {
        $bundles = [
            new AppBundle(),
            new DoctrineBundle(),
            new FrameworkBundle(),
            new MonologBundle(),
            new SecurityBundle(),
            new SensioFrameworkExtraBundle(),
            new SwiftmailerBundle(),
            new TwigBundle(),

            new DoctrineMongoDBBundle(),
            new FOSRestBundle(),
            new JMSSerializerBundle(),
            new RabbitMqBundle(),

            new HbPFApiGatewayBundle(),
            new HbPFCommonsBundle(),
            new HbPFMailerBundle(),
            new HbPFConfiguratorBundle()
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], TRUE)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__) . '/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }
}
