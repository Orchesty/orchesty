<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector\Traits;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Throwable;

/**
 * Trait ProcessExceptionTrait
 *
 * @package Hanaboso\PipesPhpSdk\Connector\Traits
 */
trait ProcessExceptionTrait
{

    /**
     * @return string
     */
    abstract public function getId(): string;

    /**
     * @param string $message
     * @param string ...$arguments
     *
     * @return ConnectorException
     */
    protected function createException(string $message, string ...$arguments): ConnectorException
    {
        $message = sprintf("Connector '%s': %s", $this->getId(), $message);

        if ($arguments) {
            $message = sprintf($message, ...$arguments);
        }

        return new ConnectorException($message, ConnectorException::CONNECTOR_FAILED_TO_PROCESS);
    }

    /**
     * @param string $key
     *
     * @return ConnectorException
     */
    protected function createMissingContentException(string $key): ConnectorException
    {
        return $this->createException("Content '%s' does not exist!", $key);
    }

    /**
     * @param string $key
     *
     * @return ConnectorException
     */
    protected function createMissingHeaderException(string $key): ConnectorException
    {
        return $this->createException("Header '%s' does not exist!", $key);
    }

    /**
     * @param string $key
     *
     * @return ConnectorException
     */
    protected function createMissingApplicationInstallException(string $key): ConnectorException
    {
        return $this->createException("ApplicationInstall with key '%s' does not exist!", $key);
    }

    /**
     * @param ProcessDto $dto
     * @param Throwable  $throwable
     * @param int        $interval
     * @param int        $maxHops
     *
     * @return OnRepeatException
     */
    protected function createRepeatException(
        ProcessDto $dto,
        Throwable $throwable,
        int $interval = 60_000,
        int $maxHops = 10
    ): OnRepeatException
    {
        $message = sprintf("Connector '%s': %s: %s", $this->getId(), get_class($throwable), $throwable->getMessage());

        return (new OnRepeatException($dto, $message, $throwable->getCode(), $throwable))
            ->setInterval($interval)
            ->setMaxHops($maxHops);
    }

}
