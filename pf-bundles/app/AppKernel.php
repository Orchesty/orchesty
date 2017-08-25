<?php declare(strict_types=1);

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use FOS\RestBundle\FOSRestBundle;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\HbPFAuthorizationBundle;
use Hanaboso\PipesFramework\HbPFCommonsBundle\HbPFCommonsBundle;
use Hanaboso\PipesFramework\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesFramework\HbPFMailerBundle\HbPFMailerBundle;
use Hanaboso\PipesFramework\HbPFMapperBundle\HbPFMapperBundle;
use Hanaboso\PipesFramework\HbPFTableParserBundle\HbPFTableParserBundle;
use Hanaboso\PipesFramework\HbPFUserBundle\HbPFUserBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
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
    public function registerBundles(): array
    {
        $bundles = [
            new DoctrineBundle(),
            new DoctrineMongoDBBundle(),
            new FOSRestBundle(),
            new FrameworkBundle(),
            new JMSSerializerBundle(),
            new SecurityBundle(),
            new SensioFrameworkExtraBundle(),
            new MonologBundle(),

            new HbPFAuthorizationBundle(),
            new HbPFCommonsBundle(),
            new HbPFConnectorBundle(),
            new HbPFMailerBundle(),
            new HbPFMapperBundle(),
            new HbPFTableParserBundle(),
            new HbPFUserBundle(),
        ];

        return $bundles;
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return __DIR__;
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return dirname(__DIR__) . '/app/cache/' . $this->getEnvironment();
    }

    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return dirname(__DIR__) . '/app/logs';
    }

    /**
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }

}
