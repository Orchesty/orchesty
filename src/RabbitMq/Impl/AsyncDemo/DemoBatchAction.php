<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/20/17
 * Time: 4:03 PM
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\AsyncDemo;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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
        return resolve();
    }

}