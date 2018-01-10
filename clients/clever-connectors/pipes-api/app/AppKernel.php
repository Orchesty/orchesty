<?php

use CleverConnectors\AppBundle\AppBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use FOS\RestBundle\FOSRestBundle;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\HbPFApiGatewayBundle;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\HbPFAuthorizationBundle;
use Hanaboso\PipesFramework\HbPFCommonsBundle\HbPFCommonsBundle;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\HbPFConfiguratorBundle;
use Hanaboso\PipesFramework\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\PipesFramework\HbPFMapperBundle\HbPFMapperBundle;
use Hanaboso\PipesFramework\HbPFMetricsBundle\HbPFMetricsBundle;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\HbPFRabbitMqBundle;
use Hanaboso\PipesFramework\HbPFUserBundle\HbPFUserBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Snc\RedisBundle\SncRedisBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
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
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new MonologBundle(),
            new DoctrineBundle(),
            new SensioFrameworkExtraBundle(),
            new FOSRestBundle(),
            new JMSSerializerBundle(),
            new DoctrineMongoDBBundle(),
            new NelmioCorsBundle(),
            new SncRedisBundle(),

            new AppBundle(),
            new HbPFAuthorizationBundle(),
            new HbPFCommonsBundle(),
            new HbPFRabbitMqBundle(),
            new HbPFConnectorBundle(),
            new HbPFMapperBundle(),
            new HbPFApiGatewayBundle(),
            new HbPFCustomNodeBundle(),
            new HbPFConfiguratorBundle(),
            new HbPFUserBundle(),
            new HbPFMetricsBundle(),
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
        //@todo temporary fix for development
        $path = $this->getEnvironment();
        if ($this->getEnvironment() != 'prod') {
            $path = $path . getenv('HOSTNAME');
        }

        return dirname(__DIR__) . '/var/cache/' . $path;
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
