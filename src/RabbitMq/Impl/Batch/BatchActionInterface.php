<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/2/17
 * Time: 10:09 AM
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Batch;

use Bunny\Message;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Interface BatchActionInterface
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\Batch
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

}