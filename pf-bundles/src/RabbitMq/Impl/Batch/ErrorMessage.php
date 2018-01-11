<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 8:22 AM
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Batch;

/**
 * Class ErrorMessage
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\Batch
 */
class ErrorMessage
{

    /**
     * @var int
     */
    private $code = 2001;

    /**
     * @var string
     */
    private $message = '';

    /**
     * @var string
     */
    private $detail = '';

    /**
     * ErrorMessage constructor.
     *
     * @param int    $code
     * @param string $message
     */
    public function __construct(int $code, string $message = '')
    {
        $this->code    = $code;
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return ErrorMessage
     */
    public function setMessage(string $message): ErrorMessage
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * @param string $detail
     *
     * @return ErrorMessage
     */
    public function setDetail(string $detail): ErrorMessage
    {
        $this->detail = $detail;

        return $this;
    }

}