<?php declare(strict_types=1);

use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\HbPFConnectorBundle;
use RabbitMqBundle\RabbitMqBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;

return [
    FrameworkBundle::class => ['all' => TRUE],
    MonologBundle::class   => ['all' => TRUE],

    HbPFCommonsBundle::class   => ['all' => TRUE],
    RabbitMqBundle::class      => ['all' => TRUE],
];
