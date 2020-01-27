<?php declare(strict_types=1);

namespace NotificationSenderTests\Integration\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\CurlHandlerAbstract;

/**
 * Class NullCurlHandler
 *
 * @package NotificationSenderTests\Integration\Model\Notification\Handler\Impl
 */
final class NullCurlHandler extends CurlHandlerAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Curl Test Sender';
    }

    /**
     * @param mixed[] $data
     *
     * @return CurlDto
     */
    public function process(array $data): CurlDto
    {
        $data;

        return new CurlDto(['one' => 'two'], ['one' => 'two']);
    }

}
