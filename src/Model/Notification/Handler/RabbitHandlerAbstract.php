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
     * @return mixed[]
     */
    public final function getRequiredSettings(): array
    {
        return [
            RabbitDto::HOST,
            RabbitDto::PORT,
            RabbitDto::VHOST,
            RabbitDto::USERNAME,
            RabbitDto::PASSWORD,
            RabbitDto::QUEUE,
        ];
    }

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @param mixed[] $data
     *
     * @return RabbitDto
     */
    abstract public function process(array $data): RabbitDto;

}
