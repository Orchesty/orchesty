<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Sender;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\NotificationSender\Model\Notification\Dto\CurlDto;

/**
 * Class CurlSender
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Sender
 */
final class CurlSender
{

    /**
     * CurlSender constructor.
     *
     * @param CurlManagerInterface $manager
     */
    public function __construct(private CurlManagerInterface $manager)
    {
    }

    /**
     * @param CurlDto $dto
     * @param mixed[] $settings
     *
     * @throws CurlException
     */
    public function send(CurlDto $dto, array $settings): void
    {
        $this->manager->send(
            (new RequestDto(
                $settings[CurlDto::METHOD],
                new Uri($settings[CurlDto::URL]),
            ))->setBody($dto->getJsonBody())->setHeaders(array_merge($settings[CurlDto::HEADERS], $dto->getHeaders())),
        );
    }

}
