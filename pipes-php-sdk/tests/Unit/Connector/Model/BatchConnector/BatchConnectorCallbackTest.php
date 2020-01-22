<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Connector\Model\BatchConnector;

use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BatchConnectorCallbackTest
 *
 * @package PipesPhpSdkTests\Unit\Connector\Model\BatchConnector
 */
final class BatchConnectorCallbackTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Model\BatchConnector\BatchConnectorCallback
     * @covers \Hanaboso\PipesPhpSdk\Connector\Model\BatchConnector\BatchConnectorCallback::getBatchService
     * @throws ConnectorException
     */
    public function testGetBatchService(): void
    {
        $connector = self::$container->get('hbpf.custom_nodes.batch_connector_action_callback');
        $connector->getBatchService('batch-null');

        self::assertFake();
    }

}
