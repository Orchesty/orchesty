<?php declare(strict_types=1);

namespace Tests\Controller\HbPFLongRunningNodeBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\Json;
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
            function (string $nodeId, array $data, array $headers): ProcessDto {
                $headers;
                self::assertEquals(['cont'], $data);
                self::assertEquals('node', $nodeId);

                return new ProcessDto();
            }
        );

        /** @var ContainerInterface $c */
        $c = self::$client->getContainer();
        $c->set('hbpf.handler.long_running', $handler);

        $this->sendPost('/longRunning/node/process', ['cont']);
        /** @var Response $res */
        $res = self::$client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
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
        self::assertEquals(2, count(Json::decode((string) $res->getContent())['items']));

        $this->sendGet('/longRunning/id/topology/topo/node/node0/getTasks');
        /** @var Response $res */
        $res = self::$client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
        self::assertEquals(1, count(Json::decode((string) $res->getContent())['items']));
    }

}
