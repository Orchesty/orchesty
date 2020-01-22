<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\CustomNode\Model\Batch;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Class NullBatchNode
 *
 * @package PipesPhpSdkTests\Integration\CustomNode\Model\Batch
 */
class NullBatchNode implements BatchInterface, CustomNodeInterface
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
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return CustomNodeInterface
     */
    public function setApplication(ApplicationInterface $application): CustomNodeInterface
    {
        $application;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string
    {
        return 'null-batch-key';
    }

}
