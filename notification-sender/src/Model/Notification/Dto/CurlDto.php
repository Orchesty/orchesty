<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Dto;

use Hanaboso\Utils\String\Json;

/**
 * Class CurlDto
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Dto
 */
final class CurlDto
{

    public const METHOD  = 'method';
    public const URL     = 'url';
    public const HEADERS = 'headers';

    /**
     * CurlDto constructor.
     *
     * @param mixed[] $body
     * @param mixed[] $headers
     */
    public function __construct(private array $body, private array $headers)
    {
    }

    /**
     * @return mixed[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getJsonBody(): string
    {
        return Json::encode($this->body);
    }

}
