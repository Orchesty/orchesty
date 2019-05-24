<?php declare(strict_types=1);

namespace Demo\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\RabbitHandlerAbstract;

/**
 * Class ExampleRabbitNotificationHandler
 *
 * @package Demo\Model\Notification\Handler\Impl
 */
final class ExampleRabbitNotificationHandler extends RabbitHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Example Rabbit Sender';
    }

    /**
     * @param array $data
     *
     * @return RabbitDto
     */
    public function process(array $data): RabbitDto
    {
        return new RabbitDto($data, $data);
    }

}
