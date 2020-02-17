<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\EmailHandlerAbstract;
use Hanaboso\Utils\String\Json;

/**
 * Class EmailNotificationHandler
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Handler\Impl
 */
final class EmailNotificationHandler extends EmailHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Email Sender';
    }

    /**
     * @param mixed[] $data
     *
     * @return EmailDto
     */
    public function process(array $data): EmailDto
    {
        $subject = $data[self::SUBJECT] ?? 'Something gone terribly wrong';
        unset($data[self::SUBJECT]);

        return new EmailDto($subject, Json::encode($data));
    }

}
