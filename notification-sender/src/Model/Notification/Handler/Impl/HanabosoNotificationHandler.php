<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\EmailHandlerAbstract;

/**
 * Class HanabosoNotificationHandler
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Handler\Impl
 */
final class HanabosoNotificationHandler extends EmailHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Hanaboso Email Sender';
    }

    /**
     * @param array $data
     *
     * @return EmailDto
     */
    public function process(array $data): EmailDto
    {
        return new EmailDto(
            'no-reply@hanaboso.com',
            'Something gone terribly wrong',
            json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

}
