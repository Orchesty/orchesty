<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFBatchBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BatchLoaderTest
 *
 * @package PipesPhpSdkTests\Unit\HbPFBatchBundle\Loader
 */
final class BatchLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var  BatchLoader
     */
    private BatchLoader $loader;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader::getBatch
     *
     * @throws Exception
     */
    public function testGetBatch(): void
    {
        $conn = $this->loader->getBatch('null');
        self::assertInstanceOf(NullBatch::class, $conn);
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = self::getContainer()->get('hbpf.loader.batch');
    }

}
