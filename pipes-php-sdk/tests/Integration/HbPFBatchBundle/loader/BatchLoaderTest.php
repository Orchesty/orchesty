<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFBatchBundle\loader;

use Exception;
use Hanaboso\PipesPhpSdk\Batch\Exception\BatchException;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BatchLoaderTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFBatchBundle\loader
 *
 * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader
 */
final class BatchLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader::getBatch
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader::getAllBeaches
     */
    public function testGetAllBatches(): void
    {
        $batch = new BatchLoader(self::getContainer());

        $fields = $batch->getAllBeaches();
        self::assertCount(1, $fields);

        $fields = $batch->getAllBeaches(['null']);
        self::assertCount(0, $fields);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader::getBatch
     *
     * @throws Exception
     */
    public function testGetBatch(): void
    {
        $batch = new BatchLoader(self::getContainer());

        self::expectException(BatchException::class);
        $batch->getBatch('null1');
    }

}
