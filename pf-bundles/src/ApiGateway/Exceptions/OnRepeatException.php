<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Exceptions;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Throwable;

/**
 * Class OnRepeatException
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Exceptions
 */
class OnRepeatException extends Exception
{

    /**
     * @var ProcessDto
     */
    private $processDto;

    /**
     * interval in ms
     *
     * @var int
     */
    private $interval = 60000;

    /**
     * @var int
     */
    private $maxHops = 3;

    /**
     * OnRepeatException constructor.
     *
     * @param ProcessDto     $processDto
     * @param string         $message
     * @param int            $code
     * @param Throwable|NULL $previous
     */
    public function __construct(ProcessDto $processDto, $message = '', $code = 0, ?Throwable $previous = NULL)
    {
        parent::__construct($message, $code, $previous);

        $this->processDto = $processDto;
    }

    /**
     * @return int
     */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * @return int
     */
    public function getMaxHops(): int
    {
        return $this->maxHops;
    }

    /**
     * @param int $interval
     *
     * @return OnRepeatException
     */
    public function setInterval(int $interval): OnRepeatException
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * @param int $maxHops
     *
     * @return OnRepeatException
     */
    public function setMaxHops(int $maxHops): OnRepeatException
    {
        $this->maxHops = $maxHops;

        return $this;
    }

    /**
     * @return ProcessDto
     */
    public function getProcessDto(): ProcessDto
    {
        return $this->processDto;
    }

}
