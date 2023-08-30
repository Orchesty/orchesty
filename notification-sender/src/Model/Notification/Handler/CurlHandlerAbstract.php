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
    abstract public function getName(): string;

    /**
     * @param mixed[] $data
     *
     * @return CurlDto
     */
    abstract public function process(array $data): CurlDto;

    /**
     * @return string
     */
    final public function getType(): string
    {
        return NotificationSenderEnum::CURL;
    }

    /**
     * @return mixed[]
     */
    final public function getRequiredSettings(): array
    {
        return [CurlDto::METHOD, CurlDto::URL, CurlDto::HEADERS];
    }

}