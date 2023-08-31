<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\CurlHandlerAbstract;

/**
 * Class CurlNotificationHandler
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Handler\Impl
 */
final class CurlNotificationHandler extends CurlHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'CURL Sender';
    }

    /**
     * @param mixed[] $data
     *
     * @return CurlDto
     */
    public function process(array $data): CurlDto
    {
        return new CurlDto($data, ['Content-Type' => 'application/json']);
    }

}
