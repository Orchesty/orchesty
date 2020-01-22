<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFLongRunningNodeBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\Utils\String\Json;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PipesPhpSdkTests\ControllerTestCaseAbstract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LongRunningNodeControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFLongRunningNodeBundle\Controller
 */
final class LongRunningNodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::processAction()
     *
     * @throws Exception
     */
    public function testProcess(): void
    {
        /** @var LongRunningNodeHandler|MockObject $handler */
        $handler = self::createMock(LongRunningNodeHandler::class);
        $handler->method('process')->willReturnCallback(
            static function (string $nodeId, array $data): ProcessDto {
                self::assertEquals(['cont'], $data);
                self::assertEquals('node', $nodeId);

                return new ProcessDto();
            }
        );

        self::$container->set('hbpf.handler.long_running', $handler);

        $this->sendPost('/longRunning/node/process', ['cont']);
        /** @var Response $res */
        $res = $this->client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::processAction
     */
    public function testProcessActionErr(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['process']);
        $handler->expects(self::any())->method('process')->willThrowException(new LongRunningNodeException());
        self::$container->set('hbpf.handler.long_running', $handler);

        $this->client->request('POST', '/longRunning/node/process', [], [], [], '{}');
        /** @var Response $res */
        $res = $this->client->getResponse();
        self::assertEquals(500, $res->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::testAction
     */
    public function testAction(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['test']);
        $handler->expects(self::any())->method('test');
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/node/process/test');
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::testAction
     */
    public function testActionErr(): void
    {
        $response = $this->sendGet('/longRunning/node/process/test');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getTasksByIdAction
     */
    public function testGetTasksByIdAction(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getTasksById']);
        $handler->expects(self::any())->method('getTasksById')->willReturn(
            [
                'limit' => 10,
                'total' => 10,
                'items' => [],
            ]
        );
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/id/topology/topo/getTasks');
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getTasksByIdAction
     */
    public function testGetTasksByIdActionErr(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getTasksById']);
        $handler->expects(self::any())->method('getTasksById')->willThrowException(new Exception());
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/id/topology/topo/getTasks');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getTasksAction
     */
    public function testGetTasksAction(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getTasks']);
        $handler->expects(self::any())->method('getTasks')->willReturn(
            [
                'limit' => 10,
                'total' => 10,
                'items' => [],
            ]
        );
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/name/topology/topo/getTasks');
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getTasksAction
     */
    public function testGetTasksActionErr(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getTasks']);
        $handler->expects(self::any())->method('getTasks')->willThrowException(new Exception());
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/name/topology/topo/getTasks');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getNodeTasksByIdAction
     */
    public function testGetNodeTasksByIdAction(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getTasksById']);
        $handler->expects(self::any())->method('getTasksById')->willReturn(
            [
                'limit' => 10,
                'total' => 10,
                'items' => [],
            ]
        );
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/id/topology/topo/node/node/getTasks');
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getNodeTasksByIdAction
     */
    public function testGetNodeTasksByIdActionErr(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getTasksById']);
        $handler->expects(self::any())->method('getTasksById')->willThrowException(new Exception());
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/id/topology/topo/node/node/getTasks');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getNodeTasksAction
     */
    public function testGetNodeTasksAction(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getTasks']);
        $handler->expects(self::any())->method('getTasks')->willReturn(
            [
                'limit' => 10,
                'total' => 10,
                'items' => [],
            ]
        );
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/name/topology/topo/node/node/getTasks');
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getNodeTasksAction
     */
    public function testGetNodeTasksActionErr(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getTasks']);
        $handler->expects(self::any())->method('getTasks')->willThrowException(new Exception());
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/name/topology/topo/node/node/getTasks');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::listOfLongRunningNodesAction
     */
    public function testListOfLongRunningNodesAction(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getAllLongRunningNodes']);
        $handler->expects(self::any())->method('getAllLongRunningNodes')->willReturn(['data' => 'data']);
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/list');
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::listOfLongRunningNodesAction
     */
    public function testListOfLongRunningNodesActionErr(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['getAllLongRunningNodes']);
        $handler
            ->expects(self::any())
            ->method('getAllLongRunningNodes')
            ->willThrowException(new InvalidArgumentException());
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendGet('/longRunning/list');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::updateLongRunningAction
     */
    public function testUpdateLongRunningAction(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['updateLongRunningNode']);
        $handler->expects(self::any())->method('updateLongRunningNode')->willReturn(['data' => 'data']);
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendPut('/longRunning/list', ['par']);
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::updateLongRunningAction
     */
    public function testUpdateLongRunningActionErr(): void
    {
        $handler = self::createPartialMock(LongRunningNodeHandler::class, ['updateLongRunningNode']);
        $handler->expects(self::any())->method('updateLongRunningNode')
            ->willThrowException(new LongRunningNodeException());
        self::$container->set('hbpf.handler.long_running', $handler);

        $response = $this->sendPut('/longRunning/list', ['par']);
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getTasksAction()
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Controller\LongRunningNodeController::getNodeTasksAction()
     *
     * @throws Exception
     */
    public function testGetTasks(): void
    {
        $name = sprintf('topo-id-%s', uniqid());

        for ($i = 0; $i < 3; $i++) {
            $doc = new LongRunningNodeData();
            $doc
                ->setTopologyId($i < 2 ? $name : 'anotherTopo')
                ->setNodeId(sprintf('node%s', $i));
            $this->dm->persist($doc);
        }
        $this->dm->flush();
        $this->dm->clear();

        $this->sendGet(sprintf('/longRunning/id/topology/%s/getTasks', $name));
        /** @var Response $res */
        $res = $this->client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
        self::assertEquals(2, count(Json::decode((string) $res->getContent())['items']));

        $this->sendGet(sprintf('/longRunning/id/topology/%s/node/node0/getTasks', $name));
        /** @var Response $res */
        $res = $this->client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
    }

}
