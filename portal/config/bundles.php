<?php declare(strict_types=1);

use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\Portal\PortalBundle;
use Hanaboso\RestBundle\RestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;

return [
    FrameworkBundle::class   => ['all' => TRUE],
    MonologBundle::class     => ['all' => TRUE],
    HbPFCommonsBundle::class => ['all' => TRUE],
    RestBundle::class        => ['all' => TRUE],
    PortalBundle::class      => ['all' => TRUE],
];
