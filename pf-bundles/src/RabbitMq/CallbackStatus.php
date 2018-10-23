<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 29.8.17
 * Time: 10:00
 */

namespace Hanaboso\PipesFramework\RabbitMq;

/**
 * Class CallbackStatus
 *
 * @package Hanaboso\PipesFramework\RabbitMq
 */
final class CallbackStatus
{

    public const SUCCESS = 1;
    public const FAILED  = 2;
    public const RESEND  = 3;

    /**
     * @var int|null
     */
    private $status = NULL;

    /**
     * @var string | null
     */
    private $statusMessage;

    /**
     * CallbackStatus constructor.
     *
     * @param int    $status
     * @param string $statusMessage
     */
    public function __construct(int $status, ?string $statusMessage = NULL)
    {
        $this->status        = $status;
        $this->statusMessage = $statusMessage;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return CallbackStatus
     */
    public function setStatus(int $status): CallbackStatus
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    /**
     * @param string $statusMessage
     *
     * @return CallbackStatus
     */
    public function setStatusMessage(?string $statusMessage): CallbackStatus
    {
        $this->statusMessage = $statusMessage;

        return $this;
    }

}
