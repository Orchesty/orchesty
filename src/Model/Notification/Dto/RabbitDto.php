<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Dto;

use Hanaboso\CommonsBundle\Utils\Json;

/**
 * Class RabbitDto
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Dto
 */
final class RabbitDto
{

    public const QUEUE    = 'queue';
    public const HOST     = 'host';
    public const PORT     = 'port';
    public const VHOST    = 'vhost';
    public const USERNAME = 'user';
    public const PASSWORD = 'password';

    /**
     * @var mixed[]
     */
    private $body;

    /**
     * @var mixed[]
     */
    private $headers;

    /**
     * RabbitDto constructor.
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
