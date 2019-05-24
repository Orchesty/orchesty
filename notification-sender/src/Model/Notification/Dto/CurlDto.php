<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Dto;

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
     * @var array
     */
    private $body;

    /**
     * @var array
     */
    private $headers;

    /**
     * CurlDto constructor.
     *
     * @param array $body
     * @param array $headers
     */
    public function __construct(array $body, array $headers)
    {
        $this->body    = $body;
        $this->headers = $headers;
    }

    /**
     * @return array
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
        return json_encode($this->body, JSON_THROW_ON_ERROR) ?: '{}';
    }

}
