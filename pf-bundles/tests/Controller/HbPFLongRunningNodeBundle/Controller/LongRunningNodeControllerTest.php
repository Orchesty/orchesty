<?php declare(strict_types=1);

namespace Tests\Controller\HbPFLongRunningNodeBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\ControllerTestCaseAbstract;

/**
 * Class LongRunningNodeControllerTest
 *
 * @package Tests\Controller\HbPFLongRunningNodeBundle\Controller
 */
final class LongRunningNodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers LongRunningNodeController::runAction()
     *
     * @throws Exception
     */
    public function testRun(): void
    {
        /** @var LongRunningNodeHandler|MockObject $handler */
        $handler = $this->createMock(LongRunningNodeHandler::class);
        $handler->method('run')->willReturnCallback(
            function (string $topologyName, string $nodeName, array $data, ?string $token = NULL): void {
                self::assertEquals('topo', $topologyName);
                self::assertEquals('test', $nodeName);
                self::assertEquals(['cont'], $data);
                self::assertEquals('token', $token);
            }
        );

        /** @var ContainerInterface $c */
        $c = $this->client->getContainer();
        $c->set('hbpf.handler.long_running', $handler);

        $this->sendPost('/longRunning/run/topology/topo/node/test/token/token', ['cont']);
    }

    /**
     * @covers LongRunningNodeController::processAction()
     *
     * @throws Exception
     */
    public function testProcess(): void
    {
        /** @var LongRunningNodeHandler|MockObject $handler */
        $handler = $this->createMock(LongRunningNodeHandler::class);
        $handler->method('process')->willReturnCallback(
            function (string $nodeId, string $data, array $headers): void {
                $headers;
                self::assertEquals(json_encode(['cont']), $data);
                self::assertEquals('node', $nodeId);
            }
        );

        /** @var ContainerInterface $c */
        $c = $this->client->getContainer();
        $c->set('hbpf.handler.long_running', $handler);

        $this->sendPost('/longRunning/node/process', ['cont']);
    }

    /**
     * @covers LongRunningNodeController::getTasksAction()
     * @covers LongRunningNodeController::getNodeTasksAction()
     *
     * @throws Exception
     */
    public function testGetTasks(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $doc = new LongRunningNodeData();
            $doc->setTopologyName($i < 2 ? 'topo' : 'anotherTopo')
                ->setNodeName('node' . $i);
            $this->dm->persist($doc);
        }
        $this->dm->flush();

        $this->sendGet('/longRunning/id/topology/topo/getTasks');
        $res = $this->client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
        self::assertEquals(2, count(json_decode($res->getContent(), TRUE)['items']));

        $this->sendGet('/longRunning/id/topology/topo/node/node0/getTasks');
        $res = $this->client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
        self::assertEquals(1, count(json_decode($res->getContent(), TRUE)['items']));
    }

}