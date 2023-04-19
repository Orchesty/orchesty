<?php declare(strict_types=1);

use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\HbPFApplicationBundle;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\HbPFBatchBundle;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\HbPFConnectorBundle;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\HbPFCustomNodeBundle;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\HbPFTableParserBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;

return [
    DebugBundle::class           => ['dev' => TRUE, 'test' => TRUE],
    FrameworkBundle::class       => ['all' => TRUE],
    HbPFApplicationBundle::class => ['all' => TRUE],
    HbPFBatchBundle::class       => ['all' => TRUE],
    HbPFCommonsBundle::class     => ['all' => TRUE],
    HbPFConnectorBundle::class   => ['all' => TRUE],
    HbPFCustomNodeBundle::class  => ['all' => TRUE],
    HbPFTableParserBundle::class => ['all' => TRUE],
    MonologBundle::class         => ['all' => TRUE],
    SecurityBundle::class        => ['all' => TRUE],
];
