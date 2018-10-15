<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Requester;

/**
 * Class ResultDto
 *
 * @package CleverConnectors\AppBundle\Model\Requester
 */
class ResultDto
{

    public const FAILED  = 'failed';
    public const SUCCESS = 'success';

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $action;

    /**
     * @var string
     */
    private $message;

    /**
     * ResultDto constructor.
     *
     * @param string $status
     * @param string $action
     * @param string $message
     */
    function __construct(string $status, string $action, string $message)
    {
        $this->status  = $status;
        $this->action  = $action;
        $this->message = $message;
    }

    /**
     * @param bool $success
     *
     * @return string
     */
    public static function statusFromBool(bool $success): string
    {
        return $success ? self::SUCCESS : self::FAILED;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

}