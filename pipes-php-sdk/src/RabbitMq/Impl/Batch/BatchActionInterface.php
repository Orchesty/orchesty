<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

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
     */
    public function batchAction(AMQPMessage $message, callable $itemCallBack): void;

    /**
     * @param string $id
     *
     * @return BatchInterface
     */
    public function getBatchService(string $id): BatchInterface;

}
