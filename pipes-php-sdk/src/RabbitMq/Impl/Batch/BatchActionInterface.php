<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use GuzzleHttp\Promise\PromiseInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Interface BatchActionInterface
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
interface BatchActionInterface
{

    /**
     * @param AMQPMessage $message
     * @param callable    $itemCallBack
     *
     * @return PromiseInterface
     */
    public function batchAction(AMQPMessage $message, callable $itemCallBack): PromiseInterface;

    /**
     * @param string $id
     *
     * @return BatchInterface
     */
    public function getBatchService(string $id): BatchInterface;

}
