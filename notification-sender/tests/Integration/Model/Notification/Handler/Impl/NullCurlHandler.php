<?php declare(strict_types=1);

namespace Tests\Integration\Model\Notification\Handler\Impl;

use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;
use Hanaboso\NotificationSender\Model\Notification\Handler\CurlHandlerAbstract;

/**
 * Class NullCurlHandler
 *
 * @package Tests\Integration\Model\Notification\Handler\Impl
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
     * @param array $data
     *
     * @return CurlDto
     */
    public function process(array $data): CurlDto
    {
        $data;

        return new CurlDto(['one' => 'two'], ['one' => 'two']);
    }

}
