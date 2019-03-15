<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Batch;

use Hanaboso\CommonsBundle\Process\ProcessDto;
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
