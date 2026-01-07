<?php declare(strict_types=1);

use EmailServiceBundle\EmailServiceBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\HbPFConnectors\HbPFConnectorsBundle;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\HbPFApplicationBundle;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\HbPFBatchBundle;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\RestBundle\RestBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;

return [
    DebugBundle::class           => ['dev' => TRUE, 'test' => TRUE],
    FrameworkBundle::class       => ['all' => TRUE],
    MonologBundle::class         => ['all' => TRUE],

    EmailServiceBundle::class    => ['all' => TRUE],
    HbPFApplicationBundle::class => ['all' => TRUE],
    HbPFBatchBundle::class       => ['all' => TRUE],
    HbPFCommonsBundle::class     => ['all' => TRUE],
    HbPFConnectorBundle::class   => ['all' => TRUE],
    HbPFConnectorsBundle::class  => ['all' => TRUE],
    HbPFCustomNodeBundle::class  => ['all' => TRUE],
    RestBundle::class            => ['all' => TRUE],
];
