<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 6.10.17
 * Time: 17:16
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Batch;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Interface BatchInterface
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\Batch
 */
interface BatchInterface
{

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface;

}