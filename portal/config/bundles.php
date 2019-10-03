<?php

use FOS\RestBundle\FOSRestBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\Portal\PortalBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;

return [
    FOSRestBundle::class         => ['all' => TRUE],
    FrameworkBundle::class       => ['all' => TRUE],
    JMSSerializerBundle::class   => ['all' => TRUE],
    MonologBundle::class         => ['all' => TRUE],

    HbPFCommonsBundle::class        => ['all' => TRUE],
    PortalBundle::class => ['all' => TRUE],
];
