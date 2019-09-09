<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\RabbitHandlerAbstract;

/**
 * Class RabbitNotificationHandler
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Handler\Impl
 */
final class RabbitNotificationHandler extends RabbitHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'AMQP Sender';
    }

    /**
     * @param array $data
     *
     * @return RabbitDto
     */
    public function process(array $data): RabbitDto
    {
        return new RabbitDto($data, ['Content-Type' => 'application/json']);
    }

}
