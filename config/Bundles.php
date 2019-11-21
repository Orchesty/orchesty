<?php declare(strict_types=1);

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use EmailServiceBundle\EmailServiceBundle;
use FOS\RestBundle\FOSRestBundle;
use Hanaboso\AclBundle\HbPFAclBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\HbPFAppStore\HbPFAppStoreBundle;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\HbPFApiGatewayBundle;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\HbPFConfiguratorBundle;
use Hanaboso\PipesFramework\HbPFLogsBundle\HbPFLogsBundle;
use Hanaboso\PipesFramework\HbPFMetricsBundle\HbPFMetricsBundle;
use Hanaboso\PipesFramework\HbPFNotificationBundle\HbPFNotificationBundle;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\HbPFApplicationBundle;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\HbPFJoinerBundle;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\HbPFLongRunningNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\HbPFMapperBundle;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\HbPFTableParserBundle;
use Hanaboso\UserBundle\HbPFUserBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use RabbitMqBundle\RabbitMqBundle;
use Snc\RedisBundle\SncRedisBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;

return [
    DebugBundle::class            => ['dev' => TRUE, 'test' => TRUE],
    DoctrineBundle::class         => ['all' => TRUE],
    DoctrineFixturesBundle::class => ['all' => TRUE],
    DoctrineMongoDBBundle::class  => ['all' => TRUE],
    FOSRestBundle::class          => ['all' => TRUE],
    FrameworkBundle::class        => ['all' => TRUE],
    JMSSerializerBundle::class    => ['all' => TRUE],
    MonologBundle::class          => ['all' => TRUE],
    SecurityBundle::class         => ['all' => TRUE],
    SncRedisBundle::class         => ['all' => TRUE],
    SwiftmailerBundle::class      => ['all' => TRUE],

    HbPFCommonsBundle::class         => ['all' => TRUE],
    HbPFAclBundle::class             => ['all' => TRUE],
    HbPFApiGatewayBundle::class      => ['all' => TRUE],
    HbPFApplicationBundle::class     => ['all' => TRUE],
    HbPFAppStoreBundle::class        => ['all' => TRUE],
    HbPFConfiguratorBundle::class    => ['all' => TRUE],
    HbPFConnectorBundle::class       => ['all' => TRUE],
    HbPFCustomNodeBundle::class      => ['all' => TRUE],
    HbPFJoinerBundle::class          => ['all' => TRUE],
    HbPFMapperBundle::class          => ['all' => TRUE],
    HbPFTableParserBundle::class     => ['all' => TRUE],
    HbPFUserBundle::class            => ['all' => TRUE],
    HbPFMetricsBundle::class         => ['all' => TRUE],
    HbPFNotificationBundle::class    => ['all' => TRUE],
    HbPFLogsBundle::class            => ['all' => TRUE],
    HbPFLongRunningNodeBundle::class => ['all' => TRUE],
    EmailServiceBundle::class        => ['all' => TRUE],
    RabbitMqBundle::class            => ['all' => TRUE],
];
