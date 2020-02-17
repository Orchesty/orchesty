<?php declare(strict_types=1);

namespace Hanaboso\NotificationSender\Model\Notification\Dto;

/**
 * Class EmailDto
 *
 * @package Hanaboso\NotificationSender\Model\Notification\Dto
 */
final class EmailDto
{

    public const EMAIL      = 'email';
    public const EMAILS     = 'emails';
    public const HOST       = 'host';
    public const PORT       = 'port';
    public const USERNAME   = 'username';
    public const PASSWORD   = 'password';
    public const ENCRYPTION = 'encryption';

    /**
     * @var string
     */
    private string $subject;

    /**
     * @var string
     */
    private string $body;

    /**
     * EmailDto constructor.
     *
     * @param string $subject
     * @param string $body
     */
    public function __construct(string $subject, string $body)
    {
        $this->subject = $subject;
        $this->body    = $body;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

}
