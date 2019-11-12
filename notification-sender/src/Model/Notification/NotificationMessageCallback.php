<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification;

use Bunny\Message;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\NotificationSender\Exception\NotificationException;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;

/**
 * Class NotificationMessageCallback
 *
 * @package Hanaboso\NotificationSender\Model\Notification
 */
final class NotificationMessageCallback implements CallbackInterface
{

    private const TYPE = 'notification_type';
    private const PIPE = 'pipes';

    /**
     * @var NotificationManager
     */
    private $manager;

    /**
     * NotificationMessageCallback constructor.
     *
     * @param NotificationManager $manager
     */
    public function __construct(NotificationManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Message    $message
     * @param Connection $connection
     * @param int        $channelId
     *
     * @throws NotificationException
     * @throws EnumException
     */
    public function processMessage(Message $message, Connection $connection, int $channelId): void
    {
        $data  = json_decode($message->content, TRUE, 512, JSON_THROW_ON_ERROR);
        $event = $data[self::PIPE][self::TYPE] ?? '';

        if (!$event) {
            throw new NotificationException(
                sprintf(
                    "Notification event not found: RabbitMQ message missing required property 'notification_type'!"
                ),
                NotificationException::NOTIFICATION_EVENT_NOT_FOUND
            );
        }

        $this->manager->send(NotificationEventEnum::isValid($event), $data);
        $connection->getChannel($channelId)->ack($message);
    }

}
