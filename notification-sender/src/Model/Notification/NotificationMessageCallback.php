<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification;

use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\NotificationSender\Exception\NotificationException;
use Hanaboso\Utils\String\Json;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;
use RabbitMqBundle\Utils\Message;

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
     * NotificationMessageCallback constructor.
     *
     * @param NotificationManager $manager
     */
    public function __construct(private NotificationManager $manager)
    {
    }

    /**
     * @param AMQPMessage $message
     * @param Connection  $connection
     * @param int         $channelId
     *
     * @throws NotificationException
     * @throws Exception
     */
    public function processMessage(AMQPMessage $message, Connection $connection, int $channelId): void
    {
        $data  = Json::decode(Message::getBody($message));
        $event = $data[self::PIPE][self::TYPE] ?? '';

        if (!$event) {
            throw new NotificationException(
                sprintf(
                    "Notification event not found: RabbitMQ message missing required property 'notification_type'!",
                ),
                NotificationException::NOTIFICATION_EVENT_NOT_FOUND,
            );
        }

        $this->manager->send(NotificationEventEnum::isValid($event), $data);
        Message::ack($message, $connection, $channelId);
    }

}
