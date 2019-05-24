<?php declare(strict_types=1);

namespace Demo\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\EmailDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\EmailHandlerAbstract;

/**
 * Class ExampleEmailNotificationHandler
 *
 * @package Demo\Model\Notification\Handler\Impl
 */
final class ExampleEmailNotificationHandler extends EmailHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Example Email Sender';
    }

    /**
     * @param array $data
     *
     * @return EmailDto
     */
    public function process(array $data): EmailDto
    {
        $data;

        return new EmailDto('', '', '');
    }

}
