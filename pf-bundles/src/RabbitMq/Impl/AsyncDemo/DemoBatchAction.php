<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Impl\AsyncDemo;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class DemoBatchAction
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\AsyncDemo
 */
class DemoBatchAction implements BatchInterface
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

        return resolve();
    }

}
