<?php

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use FOS\RestBundle\FOSRestBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\HbPFAppStore\HbPFAppStoreBundle;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\HbPFApplicationBundle;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\HbPFJoinerBundle;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\HbPFLongRunningNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\HbPFMapperBundle;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\HbPFTableParserBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use RabbitMqBundle\RabbitMqBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    DebugBundle::class           => ['dev' => TRUE, 'test' => TRUE],
    DoctrineBundle::class        => ['all' => TRUE],
    DoctrineCacheBundle::class   => ['all' => TRUE],
    DoctrineMongoDBBundle::class => ['all' => TRUE],
    FOSRestBundle::class         => ['all' => TRUE],
    FrameworkBundle::class       => ['all' => TRUE],
    JMSSerializerBundle::class   => ['all' => TRUE],
    MonologBundle::class         => ['all' => TRUE],
    SecurityBundle::class        => ['all' => TRUE],
    TwigBundle::class            => ['all' => TRUE],

    HbPFApplicationBundle::class     => ['all' => TRUE],
    HbPFAppStoreBundle::class        => ['all' => TRUE],
    HbPFCommonsBundle::class         => ['all' => TRUE],
    HbPFConnectorBundle::class       => ['all' => TRUE],
    HbPFCustomNodeBundle::class      => ['all' => TRUE],
    HbPFJoinerBundle::class          => ['all' => TRUE],
    HbPFLongRunningNodeBundle::class => ['all' => TRUE],
    HbPFMapperBundle::class          => ['all' => TRUE],
    HbPFTableParserBundle::class     => ['all' => TRUE],
    RabbitMqBundle::class            => ['all' => TRUE],
];
