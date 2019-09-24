<?php declare(strict_types=1);

namespace Tests\Controller\HbPFLongRunningNodeBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class LongRunningNodeControllerTest
 *
 * @package Tests\Controller\HbPFLongRunningNodeBundle\Controller
 */
final class LongRunningNodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers LongRunningNodeController::processAction()
     *
     * @throws Exception
     */
    public function testProcess(): void
    {
        /** @var LongRunningNodeHandler|MockObject $handler */
        $handler = self::createMock(LongRunningNodeHandler::class);
        $handler->method('process')->willReturnCallback(
            function (string $nodeId, string $data, array $headers): void {
                $headers;
                self::assertEquals(json_encode(['cont'], JSON_THROW_ON_ERROR), $data);
                self::assertEquals('node', $nodeId);
            }
        );

        /** @var ContainerInterface $c */
        $c = self::$client->getContainer();
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
            $doc->setTopologyId($i < 2 ? 'topo' : 'anotherTopo')
                ->setNodeId(sprintf('node%s', $i));
            $this->dm->persist($doc);
        }
        $this->dm->flush();

        $this->sendGet('/longRunning/id/topology/topo/getTasks');
        /** @var Response $res */
        $res = self::$client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
        self::assertEquals(2, count(json_decode((string) $res->getContent(), TRUE, 512, JSON_THROW_ON_ERROR)['items']));

        $this->sendGet('/longRunning/id/topology/topo/node/node0/getTasks');
        /** @var Response $res */
        $res = self::$client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
        self::assertEquals(1, count(json_decode((string) $res->getContent(), TRUE, 512, JSON_THROW_ON_ERROR)['items']));
    }

}
