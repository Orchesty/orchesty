<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Handler;

use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
use Hanaboso\NotificationSender\Model\Notification\Dto\RabbitDto;

/**
 * Class RabbitHandlerAbstract
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Handler
 */
abstract class RabbitHandlerAbstract
{

    /**
     * @return string
     */
    public final function getType(): string
    {
        return NotificationSenderEnum::RABBIT;
    }

    /**
     * @return array
     */
    public final function getRequiredSettings(): array
    {
        return [RabbitDto::QUEUE];
    }

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @param array $data
     *
     * @return RabbitDto
     */
    abstract public function process(array $data): RabbitDto;

}
