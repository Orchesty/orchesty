<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Connector\Model\BatchConnector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Connector\ConnectorInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Class NullBatchConnector
 *
 * @package PipesPhpSdkTests\Unit\Connector\Model\BatchConnector
 */
final class NullBatchConnector implements BatchInterface, ConnectorInterface
{

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $dto;
        $loop;
        $callbackItem;

        return new Promise(static fn($result) => $result, static fn($result) => $result);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'null-batch';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return ConnectorInterface
     */
    public function setApplication(ApplicationInterface $application): ConnectorInterface
    {
        $application;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string
    {
        return 'batch-null-key';
    }

}
