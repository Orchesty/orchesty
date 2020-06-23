<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Connector\Model\BatchConnector;

use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Connector\ConnectorInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;

/**
 * Class NullBatchConnector
 *
 * @package PipesPhpSdkTests\Unit\Connector\Model\BatchConnector
 */
final class NullBatchConnector implements BatchInterface, ConnectorInterface
{

    use BatchTrait;

    /**
     * @param ProcessDto $dto
     * @param callable   $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, callable $callbackItem): PromiseInterface
    {
        $dto;
        $callbackItem;

        return $this->createPromise();
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
