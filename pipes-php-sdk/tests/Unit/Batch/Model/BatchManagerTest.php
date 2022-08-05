<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Batch\Model;

use Exception;
use Hanaboso\PipesPhpSdk\Batch\Model\BatchManager;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\Unit\HbPFBatchBundle\Loader\NullBatch;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BatchManagerTest
 *
 * @package PipesPhpSdkTests\Unit\Batch\Model
 */
final class BatchManagerTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Batch\Model\BatchManager::processAction
     * @covers \Hanaboso\PipesPhpSdk\Batch\BatchAbstract::getApplicationKey
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        /** @var BatchManager $manager */
        $manager = self::getContainer()->get('hbpf.manager.batch');

        /** @var NullBatch $batch */
        $batch = self::getContainer()->get('hbpf.batch.null');
        $dto   = $manager->processAction($batch, new Request());
        self::assertEquals('[]', $dto->getBridgeData());
    }

}
