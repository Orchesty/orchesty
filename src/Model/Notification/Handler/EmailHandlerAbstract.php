<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Handler;

use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;

/**
 * Class EmailHandlerAbstract
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Handler
 */
abstract class EmailHandlerAbstract
{

    public const SUBJECT = 'subject';

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @param mixed[] $data
     *
     * @return EmailDto
     */
    abstract public function process(array $data): EmailDto;

    /**
     * @return string
     */
    final public function getType(): string
    {
        return NotificationSenderEnum::EMAIL;
    }

    /**
     * @return mixed[]
     */
    final public function getRequiredSettings(): array
    {
        return [
            EmailDto::HOST,
            EmailDto::PORT,
            EmailDto::USERNAME,
            EmailDto::PASSWORD,
            EmailDto::ENCRYPTION,
            EmailDto::EMAILS,
            EmailDto::EMAIL,
        ];
    }

}
