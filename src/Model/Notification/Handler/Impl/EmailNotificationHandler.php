<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Handler\Impl;

use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\EmailHandlerAbstract;

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
        return new EmailDto(
            'no-reply@hanaboso.com',
            'Something gone terribly wrong',
            Json::encode($data)
        );
    }

}
