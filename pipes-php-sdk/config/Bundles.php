<?php

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use FOS\RestBundle\FOSRestBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\PipesPhpSdk\HbPFAuthorizationBundle\HbPFAuthorizationBundle;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\HbPFJoinerBundle;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\HbPFLongRunningNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\HbPFMapperBundle;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\HbPFTableParserBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use RabbitMqBundle\RabbitMqBundle;
use Snc\RedisBundle\SncRedisBundle;

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class          => ['all' => TRUE],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class            => ['all' => TRUE],
    Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle::class => ['all' => TRUE],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class           => ['all' => TRUE],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class   => ['all' => TRUE],
    Symfony\Bundle\MonologBundle\MonologBundle::class              => ['all' => TRUE],
    Symfony\Bundle\DebugBundle\DebugBundle::class                  => ['dev' => TRUE, 'test' => TRUE],
    DoctrineMongoDBBundle::class                                   => ['all' => TRUE],
    JMSSerializerBundle::class                                     => ['all' => TRUE],
    FOSRestBundle::class                                           => ['all' => TRUE],
    SncRedisBundle::class                                          => ['all' => TRUE],

    HbPFCommonsBundle::class         => ['all' => TRUE],
    HbPFAuthorizationBundle::class   => ['all' => TRUE],
    HbPFConnectorBundle::class       => ['all' => TRUE],
    HbPFCustomNodeBundle::class      => ['all' => TRUE],
    HbPFJoinerBundle::class          => ['all' => TRUE],
    HbPFMapperBundle::class          => ['all' => TRUE],
    HbPFTableParserBundle::class     => ['all' => TRUE],
    HbPFLongRunningNodeBundle::class => ['all' => TRUE],
    RabbitMqBundle::class            => ['all' => TRUE],
];
