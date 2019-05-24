<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Sender;

use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Connection\ConnectionManager;
use Throwable;

/**
 * Class RabbitSender
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Sender
 */
final class RabbitSender
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * RabbitSender constructor.
     *
     * @param ConnectionManager $manager
     */
    public function __construct(ConnectionManager $manager)
    {
        $this->connection = $manager->getConnection();
    }

    /**
     * @param RabbitDto $dto
     * @param array     $settings
     *
     * @throws Throwable
     */
    public function send(RabbitDto $dto, array $settings): void
    {
        $channel = $this->connection->getChannel($this->connection->createChannel());
        $channel->queueDeclare($settings[RabbitDto::QUEUE]);

        try {
            $channel->publish($dto->getJsonBody(), $dto->getHeaders(), '', $settings[RabbitDto::QUEUE]);
        } finally {
            $channel->close();
        }
    }

}
