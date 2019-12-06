<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Dto;

use Hanaboso\CommonsBundle\Utils\Json;

/**
 * Class CurlDto
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Dto
 */
final class CurlDto
{

    public const METHOD = 'method';
    public const URL    = 'url';

    /**
     * @var mixed[]
     */
    private $body;

    /**
     * @var mixed[]
     */
    private $headers;

    /**
     * CurlDto constructor.
     *
     * @param mixed[] $body
     * @param mixed[] $headers
     */
    public function __construct(array $body, array $headers)
    {
        $this->body    = $body;
        $this->headers = $headers;
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
        return Json::encode($this->body) ?: '{}';
    }

}
