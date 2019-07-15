<?php

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use EmailServiceBundle\EmailServiceBundle;
use FOS\RestBundle\FOSRestBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\HbPFApiGatewayBundle;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\HbPFConfiguratorBundle;
use Hanaboso\PipesFramework\HbPFLogsBundle\HbPFLogsBundle;
use Hanaboso\PipesFramework\HbPFMetricsBundle\HbPFMetricsBundle;
use Hanaboso\PipesFramework\HbPFNotificationBundle\HbPFNotificationBundle;
use Hanaboso\PipesPhpSdk\HbPFAuthorizationBundle\HbPFAuthorizationBundle;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\HbPFLongRunningNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\HbPFMapperBundle;
use Hanaboso\UserBundle\HbPFUserBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use RabbitMqBundle\RabbitMqBundle;
use Snc\RedisBundle\SncRedisBundle;

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class          => ['all' => TRUE],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class            => ['all' => TRUE],
    Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle::class => ['all' => TRUE],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class           => ['all' => TRUE],
    Symfony\Bundle\MonologBundle\MonologBundle::class              => ['all' => TRUE],
    Symfony\Bundle\TwigBundle\TwigBundle::class                    => ['all' => TRUE],
    Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle::class      => ['all' => TRUE],
    Symfony\Bundle\DebugBundle\DebugBundle::class                  => ['dev' => TRUE, 'test' => TRUE],
    DoctrineMongoDBBundle::class                                   => ['all' => TRUE],
    FOSRestBundle::class                                           => ['all' => TRUE],
    JMSSerializerBundle::class                                     => ['all' => TRUE],
    NelmioCorsBundle::class                                        => ['all' => TRUE],
    SncRedisBundle::class                                          => ['all' => TRUE],

    HbPFApiGatewayBundle::class      => ['all' => TRUE],
    HbPFAuthorizationBundle::class   => ['all' => TRUE],
    HbPFConfiguratorBundle::class    => ['all' => TRUE],
    HbPFCommonsBundle::class         => ['all' => TRUE],
    HbPFConnectorBundle::class       => ['all' => TRUE],
    HbPFCustomNodeBundle::class      => ['all' => TRUE],
    HbPFMapperBundle::class          => ['all' => TRUE],
    HbPFMetricsBundle::class         => ['all' => TRUE],
    HbPFUserBundle::class            => ['all' => TRUE],
    RabbitMqBundle::class            => ['all' => TRUE],
    EmailServiceBundle::class        => ['all' => TRUE],
    HbPFLogsBundle::class            => ['all' => TRUE],
    HbPFNotificationBundle::class    => ['all' => TRUE],
    HbPFLongRunningNodeBundle::class => ['all' => TRUE],
];
