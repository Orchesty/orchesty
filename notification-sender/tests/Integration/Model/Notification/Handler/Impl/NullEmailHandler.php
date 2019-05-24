<?php declare(strict_types=1);

namespace Tests\Integration\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\EmailHandlerAbstract;

/**
 * Class NullEmailHandler
 *
 * @package Tests\Integration\Model\Notification\Handler\Impl
 */
final class NullEmailHandler extends EmailHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Email Test Sender';
    }

    /**
     * @param array $data
     *
     * @return EmailDto
     */
    public function process(array $data): EmailDto
    {
        $data;

        return new EmailDto('email@example.com', 'Subject', 'Body');
    }

}
