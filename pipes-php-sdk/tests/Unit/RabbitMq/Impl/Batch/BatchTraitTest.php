<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch;

use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BatchTraitTest
 *
 * @package PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch
 */
final class BatchTraitTest extends KernelTestCaseAbstract
{

    use BatchTrait;

    /**
     *
     */
    public function testCreatePromise(): void
    {
        $promise = $this->createPromise(static fn() => 'test');
        $promise->wait();
        self::assertFake();

        $promise = $this->createPromise();
        $promise->wait();
        self::assertFake();
    }

}
