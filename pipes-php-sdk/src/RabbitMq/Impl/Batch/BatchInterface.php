<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Interface BatchInterface
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
interface BatchInterface
{

    /**
     * @param ProcessDto $dto
     * @param callable   $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, callable $callbackItem): PromiseInterface;

}
