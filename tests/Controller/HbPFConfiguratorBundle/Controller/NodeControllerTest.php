<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConfiguratorBundle\Controller;

use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tests\ControllerTestCaseAbstract;

/**
 * Class NodeControllerTest
 *
 * @package Tests\Controller\HbPFConfiguratorBundle\Controller
 */
final class NodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers NodeController::getNodesAction()
     */
    public function testGetNodes(): void
    {
        $returnValue = ['abc'];

        $this->prepareNodeHandlerMock('getNodes', $returnValue);

        $this->client->request('GET', '/api/gateway/topologies/abc123/nodes', [], [], []);

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($returnValue, $content);
    }

    /**
     * @covers NodeController::getNodeAction()
     */
    public function testGetNode(): void
    {
        $returnValue = ['abc'];

        $this->prepareNodeHandlerMock('getNode', $returnValue);

        $this->client->request('GET', '/api/gateway/nodes/abc123', [], [], []);

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($returnValue, $content);
    }

    /**
     * @covers NodeController::updateNodeAction()
     */
    public function testUpdateNode(): void
    {
        $returnValue = ['abc'];

        $this->prepareNodeHandlerMock('updateNode', $returnValue);

        $this->client->request('PATCH', '/api/gateway/nodes/abc123', [], [], []);

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($returnValue, $content);
    }

    /**
     * @param string $methodName
     * @param bool   $returnValue
     */
    private function prepareNodeHandlerMock(string $methodName, $returnValue = TRUE): void
    {
        $nodeHandlerMock = $this->getMockBuilder(NodeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nodeHandlerMock->method($methodName)->willReturn($returnValue);

        $this->client->getContainer()->set('hbpf.configurator.handler.node', $nodeHandlerMock);
    }

}