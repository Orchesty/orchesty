<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use Bunny\Message;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Interface BatchActionInterface
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
interface BatchActionInterface
{

    /**
     * @param Message       $message
     * @param LoopInterface $loop
     * @param callable      $itemCallBack
     *
     * @return PromiseInterface
     */
    public function batchAction(Message $message, LoopInterface $loop, callable $itemCallBack): PromiseInterface;

    /**
     * @param string $id
     *
     * @return BatchInterface
     */
    public function getBatchService(string $id): BatchInterface;

}
