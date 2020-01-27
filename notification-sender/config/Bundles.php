<?php declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use EmailServiceBundle\EmailServiceBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\NotificationSender\NotificationSenderBundle;
use Hanaboso\RestBundle\RestBundle;
use RabbitMqBundle\RabbitMqBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;

return [
    FrameworkBundle::class       => ['all' => TRUE],
    MonologBundle::class         => ['all' => TRUE],
    SwiftmailerBundle::class     => ['all' => TRUE],
    DoctrineMongoDBBundle::class => ['all' => TRUE],

    HbPFCommonsBundle::class        => ['all' => TRUE],
    EmailServiceBundle::class       => ['all' => TRUE],
    NotificationSenderBundle::class => ['all' => TRUE],
    RabbitMqBundle::class           => ['all' => TRUE],
    RestBundle::class               => ['all' => TRUE],
];
