<?php declare(strict_types=1);

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use EmailServiceBundle\EmailServiceBundle;
use FOS\RestBundle\FOSRestBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\NotificationSender\NotificationSenderBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use RabbitMqBundle\RabbitMqBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;

return [
    DoctrineMongoDBBundle::class => ['all' => TRUE],
    FOSRestBundle::class         => ['all' => TRUE],
    FrameworkBundle::class       => ['all' => TRUE],
    JMSSerializerBundle::class   => ['all' => TRUE],
    MonologBundle::class         => ['all' => TRUE],
    SwiftmailerBundle::class     => ['all' => TRUE],

    EmailServiceBundle::class       => ['all' => TRUE],
    HbPFCommonsBundle::class        => ['all' => TRUE],
    NotificationSenderBundle::class => ['all' => TRUE],
    RabbitMqBundle::class           => ['all' => TRUE],
];
