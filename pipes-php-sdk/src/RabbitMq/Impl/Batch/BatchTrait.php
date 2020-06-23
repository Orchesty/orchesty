<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Trait BatchTrait
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
trait BatchTrait
{

    /**
     * @param callable|null $resolve
     *
     * @return PromiseInterface
     */
    public function createPromise(?callable $resolve = NULL): PromiseInterface
    {
        if (!$resolve) {
            $resolve = static fn() => 'waited';
        }

        $promise = new Promise(
            static function () use (&$promise, $resolve): void {
                if ($promise) {
                    $promise->resolve($resolve());
                }
            },
        );

        return $promise;
    }

}
