<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Handler\ConnectorHandler;
use ReflectionException;
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
     * @throws Exception
     */
    public function testGetNodes(): void
    {
        $returnValue = ['abc'];

        $this->prepareNodeHandlerMock('getNodes', $returnValue);

        $this->client->request('GET', '/api/topologies/abc123/nodes', [], [], []);

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($returnValue, $content);
    }

    /**
     * @covers NodeController::getNodeAction()
     * @throws Exception
     */
    public function testGetNode(): void
    {
        $returnValue = ['abc'];

        $this->prepareNodeHandlerMock('getNode', $returnValue);

        $this->client->request('GET', '/api/nodes/abc123', [], [], []);

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($returnValue, $content);
    }

    /**
     * @covers NodeController::updateNodeAction()
     * @throws Exception
     */
    public function testUpdateNode(): void
    {
        $returnValue = ['abc'];

        $this->prepareNodeHandlerMock('updateNode', $returnValue);

        $this->client->request('PATCH', '/api/nodes/abc123', [], [], []);

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($returnValue, $content);
    }

    /**
     * @param string $methodName
     * @param bool   $returnValue
     *
     * @throws Exception
     */
    private function prepareNodeHandlerMock(string $methodName, $returnValue = TRUE): void
    {
        $nodeHandlerMock = $this->getMockBuilder(NodeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nodeHandlerMock->method($methodName)->willReturn($returnValue);

        $this->client->getContainer()->set('hbpf.configurator.handler.node', $nodeHandlerMock);
    }

    /**
     * @covers NodeController::listOfNodesAction()
     *
     * @throws Exception
     */
    public function testListOfNodes(): void
    {
        $type = 'connector';
        $this->prepareNodeMock(ConnectorHandler::class, 'getConnectors');

        $response = $this->sendGet(sprintf('/api/nodes/%s/list_nodes', $type));
        $content  = $response->content;

        self::assertEquals(200, $response->status);
        self::assertEquals(['magento2.orders', 'magento2.modules', 'magento2.customers'], (array) $content);

        $type     = 'config';
        $response = $this->sendGet(sprintf('/api/nodes/%s/list_nodes', $type));

        self::assertEquals(404, $response->status);
    }

    /**
     * @param string $className
     * @param string $methodName
     *
     * @throws ReflectionException
     */
    private function prepareNodeMock(string $className, string $methodName): void
    {
        $nodeHandlerMock = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();

        $nodeHandlerMock->method($methodName);
    }

}
