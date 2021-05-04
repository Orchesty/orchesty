<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Enum\StateEnum;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class LongRunningNodeControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LongRunningNodeController
 */
final class LongRunningNodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LongRunningNodeController::processAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/LongRunningNodeController/processActionRequest.json',
            [],
            [],
            [],
            ['HTTP_pf-doc-id' => $this->createLongRunningNodeData()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LongRunningNodeController::testAction
     *
     * @throws Exception
     */
    public function testTestAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/LongRunningNodeController/testActionRequest.json',
            [],
            [],
            [],
            ['HTTP_pf-doc-id' => $this->createLongRunningNodeData()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LongRunningNodeController::getTasksByAction
     *
     * @throws Exception
     */
    public function testGetTasksByIdAction(): void
    {
        $this->createLongRunningNodeData('123456789');

        $this->assertResponse(
            __DIR__ . '/data/LongRunningNodeController/getTasksByIdRequest.json',
            ['id' => '123456789', 'created' => '2010-10-10 10:10:10', 'updated' => '2010-10-10 10:10:10'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LongRunningNodeController::getTasksAction
     *
     * @throws Exception
     */
    public function testGetTasksAction(): void
    {
        $this->createLongRunningNodeData('', '123456789');

        $this->assertResponse(
            __DIR__ . '/data/LongRunningNodeController/getTasksRequest.json',
            ['id' => '123456789', 'created' => '2010-10-10 10:10:10', 'updated' => '2010-10-10 10:10:10'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LongRunningNodeController::getNodeTasksByIdAction
     *
     * @throws Exception
     */
    public function testGetNodeTasksByIdAction(): void
    {
        $this->createLongRunningNodeData('123456789', '', '123456789');

        $this->assertResponse(
            __DIR__ . '/data/LongRunningNodeController/getNodeTasksByIdRequest.json',
            ['id' => '123456789', 'created' => '2010-10-10 10:10:10', 'updated' => '2010-10-10 10:10:10'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LongRunningNodeController::getNodeTasksAction
     *
     * @throws Exception
     */
    public function testGetNodeTasksByAction(): void
    {
        $this->createLongRunningNodeData('', '123456789', '', '123456789');

        $this->assertResponse(
            __DIR__ . '/data/LongRunningNodeController/getNodeTasksRequest.json',
            ['id' => '123456789', 'created' => '2010-10-10 10:10:10', 'updated' => '2010-10-10 10:10:10'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LongRunningNodeController::updateLongRunningAction
     *
     * @throws Exception
     */
    public function testUpdateLongRunningAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/LongRunningNodeController/updateLongRunningRequest.json',
            ['created' => '2010-10-10 10:10:10', 'updated' => '2010-10-10 10:10:10'],
            [':id' => $this->createLongRunningNodeData()->getId()],
        );
    }

    /**
     * @param string $topologyId
     * @param string $topologyName
     * @param string $nodeId
     * @param string $nodeName
     *
     * @return LongRunningNodeData
     * @throws Exception
     */
    private function createLongRunningNodeData(
        string $topologyId = '',
        string $topologyName = '',
        string $nodeId = '',
        string $nodeName = '',
    ): LongRunningNodeData
    {
        $longRunningData = (new LongRunningNodeData())
            ->setParentId('1')
            ->setCorrelationId('2')
            ->setSequenceId('3')
            ->setProcessId('7')
            ->setState(StateEnum::NEW)
            ->setAuditLogs([])
            ->setUpdatedBy('4')
            ->setData('data')
            ->setContentType('string')
            ->setTopologyId($topologyId)
            ->setTopologyName($topologyName)
            ->setNodeId($nodeId)
            ->setNodeName($nodeName);

        $this->pfd($longRunningData);

        return $longRunningData;
    }

}
