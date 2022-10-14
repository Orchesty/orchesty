<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFBatchBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\Dto\CommonObjectDto;
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
     * @covers \Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader::getList
     *
     * @throws Exception
     */
    public function testListBatches(): void
    {
        $conn   = $this->loader->getList();
        $assert = new CommonObjectDto('0', 'null-key');
        self::assertEquals($assert, $conn[0]);
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
