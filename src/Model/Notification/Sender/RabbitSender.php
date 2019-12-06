<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Sender;

use Bunny\Channel;
use Bunny\Client;
use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
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
        $client = new Client(
            [
                'host'     => $settings[RabbitDto::HOST],
                'port'     => $settings[RabbitDto::PORT],
                'vhost'    => $settings[RabbitDto::VHOST],
                'user'     => $settings[RabbitDto::USERNAME],
                'password' => $settings[RabbitDto::PASSWORD],
            ]
        );

        $client->connect();

        /** @var Channel $channel */
        $channel = $client->channel();
        $channel->queueDeclare($settings[RabbitDto::QUEUE]);
        $channel->publish($dto->getJsonBody(), $dto->getHeaders(), '', $settings[RabbitDto::QUEUE]);
        $channel->close();
    }

}
