<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Handler;

use Hanaboso\CommonsBundle\Enum\NotificationSenderEnum;
use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;

/**
 * Class CurlHandlerAbstract
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Handler
 */
abstract class CurlHandlerAbstract
{

    /**
     * @return string
     */
    public final function getType(): string
    {
        return NotificationSenderEnum::CURL;
    }

    /**
     * @return mixed[]
     */
    public final function getRequiredSettings(): array
    {
        return [CurlDto::METHOD, CurlDto::URL];
    }

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @param mixed[] $data
     *
     * @return CurlDto
     */
    abstract public function process(array $data): CurlDto;

}
