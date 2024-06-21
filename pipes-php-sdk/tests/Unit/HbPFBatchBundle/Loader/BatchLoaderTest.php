<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFBatchBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\Dto\CommonObjectDto;
use Hanaboso\PipesPhpSdk\HbPFBatchBundle\Loader\BatchLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BatchLoaderTest
 *
 * @package PipesPhpSdkTests\Unit\HbPFBatchBundle\Loader
 */
#[CoversClass(BatchLoader::class)]
final class BatchLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var  BatchLoader
     */
    private BatchLoader $loader;

    /**
     * @throws Exception
     */
    public function testGetBatch(): void
    {
        $conn = $this->loader->getBatch('null');
        self::assertInstanceOf(NullBatch::class, $conn);
    }

    /**
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
