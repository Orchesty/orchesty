<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Sender;

use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use RabbitMqBundle\Utils\Message;
use Throwable;

/**
 * Class RabbitSender
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Sender
 */
final class RabbitSender
{

    /**
     * @param RabbitDto $dto
     * @param mixed[]   $settings
     *
     * @throws Throwable
     */
    public function send(RabbitDto $dto, array $settings): void
    {
        $client = new AMQPStreamConnection(
            $settings[RabbitDto::HOST],
            $settings[RabbitDto::PORT],
            $settings[RabbitDto::USERNAME],
            $settings[RabbitDto::PASSWORD],
            $settings[RabbitDto::VHOST],
        );

        $client->reconnect();

        $channel = $client->channel();
        $channel->queue_declare($settings[RabbitDto::QUEUE]);
        $channel->basic_publish(
            Message::create($dto->getJsonBody(), $dto->getHeaders()),
            '',
            $settings[RabbitDto::QUEUE],
        );

        $channel->close();
        $client->close();
    }

}
