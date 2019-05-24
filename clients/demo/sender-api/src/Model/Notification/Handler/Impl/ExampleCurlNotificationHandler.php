<?php declare(strict_types=1);

namespace Demo\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\CurlHandlerAbstract;

/**
 * Class ExampleCurlNotificationHandler
 *
 * @package Demo\Model\Notification\Handler\Impl
 */
final class ExampleCurlNotificationHandler extends CurlHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Example Curl Sender';
    }

    /**
     * @param array $data
     *
     * @return CurlDto
     */
    public function process(array $data): CurlDto
    {
        return new CurlDto($data, $data);
    }

}
