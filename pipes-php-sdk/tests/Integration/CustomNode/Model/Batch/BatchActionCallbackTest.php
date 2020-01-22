<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\CustomNode\Model\Batch;

use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BatchActionCallbackTest
 *
 * @package PipesPhpSdkTests\Integration\CustomNode\Model\Batch
 */
final class BatchActionCallbackTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Model\Batch\BatchActionCallback
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Model\Batch\BatchActionCallback::getBatchService
     *
     * @throws CustomNodeException
     */
    public function testGetBatchService(): void
    {
        $batch = self::$container->get('hbpf.custom_nodes.batch_action_callback');
        $batch = $batch->getBatchService('batch-null');

        self::assertInstanceOf(NullBatchNode::class, $batch);
    }

}
