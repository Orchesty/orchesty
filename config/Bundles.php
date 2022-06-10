<?php declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use EmailServiceBundle\EmailServiceBundle;
use Hanaboso\AclBundle\HbPFAclBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\HbPFAppStore\HbPFAppStoreBundle;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\HbPFApiGatewayBundle;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\HbPFConfiguratorBundle;
use Hanaboso\PipesFramework\HbPFLogsBundle\HbPFLogsBundle;
use Hanaboso\PipesFramework\HbPFMetricsBundle\HbPFMetricsBundle;
use Hanaboso\PipesFramework\HbPFNotificationBundle\HbPFNotificationBundle;
use Hanaboso\PipesFramework\HbPFUserBundle\HbPFUsersBundle;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\HbPFUserTaskBundle;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\HbPFApplicationBundle;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\HbPFTableParserBundle;
use Hanaboso\RestBundle\RestBundle;
use Hanaboso\UserBundle\HbPFUserBundle;
use RabbitMqBundle\RabbitMqBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;

return [
    DoctrineMongoDBBundle::class => ['all' => TRUE],
    FrameworkBundle::class       => ['all' => TRUE],
    MonologBundle::class         => ['all' => TRUE],
    SecurityBundle::class        => ['all' => TRUE],

    HbPFCommonsBundle::class         => ['all' => TRUE],
    HbPFAclBundle::class             => ['all' => TRUE],
    HbPFApiGatewayBundle::class      => ['all' => TRUE],
    HbPFApplicationBundle::class     => ['all' => TRUE],
    HbPFAppStoreBundle::class        => ['all' => TRUE],
    HbPFConfiguratorBundle::class    => ['all' => TRUE],
    HbPFConnectorBundle::class       => ['all' => TRUE],
    HbPFCustomNodeBundle::class      => ['all' => TRUE],
    HbPFTableParserBundle::class     => ['all' => TRUE],
    HbPFUserBundle::class            => ['all' => TRUE],
    HbPFMetricsBundle::class         => ['all' => TRUE],
    HbPFNotificationBundle::class    => ['all' => TRUE],
    HbPFLogsBundle::class            => ['all' => TRUE],
    EmailServiceBundle::class        => ['all' => TRUE],
    RabbitMqBundle::class            => ['all' => TRUE],
    RestBundle::class                => ['all' => TRUE],
    HbPFUsersBundle::class           => ['all' => TRUE],
    HbPFUserTaskBundle::class        => ['all' => TRUE],
];
