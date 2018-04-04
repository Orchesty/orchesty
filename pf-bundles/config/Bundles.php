<?php

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use EmailServiceBundle\EmailServiceBundle;
use FOS\RestBundle\FOSRestBundle;
use Hanaboso\PipesFramework\HbPFAclBundle\HbPFAclBundle;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\HbPFApiGatewayBundle;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\HbPFAuthorizationBundle;
use Hanaboso\PipesFramework\HbPFCommonsBundle\HbPFCommonsBundle;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\HbPFConfiguratorBundle;
use Hanaboso\PipesFramework\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\PipesFramework\HbPFJoinerBundle\HbPFJoinerBundle;
use Hanaboso\PipesFramework\HbPFLogsBundle\HbPFLogsBundle;
use Hanaboso\PipesFramework\HbPFMapperBundle\HbPFMapperBundle;
use Hanaboso\PipesFramework\HbPFMetricsBundle\HbPFMetricsBundle;
use Hanaboso\PipesFramework\HbPFNotificationBundle\HbPFNotificationBundle;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\HbPFRabbitMqBundle;
use Hanaboso\PipesFramework\HbPFTableParserBundle\HbPFTableParserBundle;
use Hanaboso\PipesFramework\HbPFUserBundle\HbPFUserBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Snc\RedisBundle\SncRedisBundle;

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class                => ['all' => TRUE],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class                  => ['all' => TRUE],
    Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle::class       => ['all' => TRUE],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class                 => ['all' => TRUE],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class         => ['all' => TRUE],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => TRUE],
    Symfony\Bundle\MonologBundle\MonologBundle::class                    => ['all' => TRUE],
    Symfony\Bundle\TwigBundle\TwigBundle::class                          => ['all' => TRUE],
    Symfony\Bundle\DebugBundle\DebugBundle::class                        => ['dev' => TRUE, 'test' => TRUE],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class            => ['dev' => TRUE, 'test' => TRUE],
    Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle::class            => ['all' => TRUE],
    DoctrineMongoDBBundle::class                                         => ['all' => TRUE],
    FOSRestBundle::class                                                 => ['all' => TRUE],
    JMSSerializerBundle::class                                           => ['all' => TRUE],
    SncRedisBundle::class                                                => ['all' => TRUE],

    HbPFAclBundle::class           => ['all' => TRUE],
    HbPFApiGatewayBundle::class    => ['all' => TRUE],
    HbPFAuthorizationBundle::class => ['all' => TRUE],
    HbPFCommonsBundle::class       => ['all' => TRUE],
    HbPFConfiguratorBundle::class  => ['all' => TRUE],
    HbPFConnectorBundle::class     => ['all' => TRUE],
    HbPFCustomNodeBundle::class    => ['all' => TRUE],
    HbPFJoinerBundle::class        => ['all' => TRUE],
    HbPFMapperBundle::class        => ['all' => TRUE],
    HbPFTableParserBundle::class   => ['all' => TRUE],
    HbPFUserBundle::class          => ['all' => TRUE],
    HbPFRabbitMqBundle::class      => ['all' => TRUE],
    HbPFMetricsBundle::class       => ['all' => TRUE],
    HbPFNotificationBundle::class  => ['all' => TRUE],
    EmailServiceBundle::class      => ['all' => TRUE],
    HbPFLogsBundle::class          => ['all' => TRUE],
];