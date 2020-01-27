<?php declare(strict_types=1);

namespace NotificationSenderTests\Integration\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\RabbitHandlerAbstract;

/**
 * Class NullRabitHandler
 *
 * @package NotificationSenderTests\Integration\Model\Notification\Handler\Impl
 */
final class NullRabitHandler extends RabbitHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Rabbit Test Sender';
    }

    /**
     * @param mixed[] $data
     *
     * @return RabbitDto
     */
    public function process(array $data): RabbitDto
    {
        $data;

        return new RabbitDto(['one' => 'two'], ['one' => 'two']);
    }

}
