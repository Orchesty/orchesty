<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFBatchBundle\loader;

use Exception;
use Hanaboso\PipesPhpSdk\Batch\Exception\BatchException;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BatchLoaderTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFBatchBundle\loader
 *
 * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader
 */
#[CoversClass(BatchLoader::class)]
final class BatchLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testGetAllBatches(): void
    {
        $batch = new BatchLoader(self::getContainer());

        $fields = $batch->getAllBatches();
        self::assertCount(1, $fields);

        $fields = $batch->getAllBatches(['null']);
        self::assertCount(0, $fields);
    }

    /**
     * @throws Exception
     */
    public function testGetBatch(): void
    {
        $batch = new BatchLoader(self::getContainer());

        self::expectException(BatchException::class);
        $batch->getBatch('null1');
    }

}
