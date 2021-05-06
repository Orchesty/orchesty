<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\LockException;
use Exception;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class NodeControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController
 */
final class NodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::getNodesAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::getNodes
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::getNodeData
     *
     * @throws Exception
     */
    public function testGetNodes(): void
    {
        $topology = new Topology();
        $this->dm->persist($topology);
        $node = (new Node())->setTopology($topology->getId());
        $this->pfd($node);

        $this->assertResponse(
            __DIR__ . '/data/Node/getNodesRequest.json',
            [
                '_id'         => '5e329eb233609f28e8613114',
                'topology_id' => '5e329eb233609f28e8613113',
            ],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::getNodeAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::getNode
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::getNodeData
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::getNodeById
     *
     * @throws Exception
     */
    public function testGetNode(): void
    {
        $node = new Node();
        $node->setTopology('1');
        $this->pfd($node);

        $this->assertResponse(
            __DIR__ . '/data/Node/getNodeRequest.json',
            ['_id' => '5e329f9b5ef3694da71d42b3'],
            [':id' => $node->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::getNodeAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::getNode
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::getNodeById
     *
     * @throws Exception
     */
    public function testGetNodeNotFound(): void
    {
        $this->assertResponse(__DIR__ . '/data/Node/getNodeNotFoundRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::getNodeAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::getNode
     *
     * @throws Exception
     */
    public function testGetNodeErr(): void
    {
        $this->prepareNodeHandlerMock();

        $node = new Node();
        $this->pfd($node);

        $this->assertResponse(__DIR__ . '/data/Node/getNodeErrRequest.json', [], [':id' => $node->getId()]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::updateNodeAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::updateNode
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler::getNodeById
     *
     * @throws Exception
     */
    public function testUpdateNode(): void
    {
        $node = new Node();
        $this->pfd($node);

        $this->assertResponse(
            __DIR__ . '/data/Node/updateNodeRequest.json',
            ['_id' => '5e32a3bf1280c6296f258c83'],
            [':id' => $node->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\NodeController::updateNodeAction
     *
     * @throws Exception
     */
    public function testUpdateErrNode(): void
    {
        $node = new Node();
        $this->pfd($node);

        $this->assertResponse(
            __DIR__ . '/data/Node/updateNodeErrRequest.json',
            ['_id' => '5e32a3bf1280c6296f258c83'],
            [':id' => $node->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testListOfNodes(): void
    {
        $type = 'connector';
        $this->prepareNodeMock();

        $response = $this->sendGet(sprintf('/api/nodes/%s/list_nodes', $type));
        $content  = $response->content;

        self::assertEquals(200, $response->status);
        self::assertEquals(['null'], (array) $content);

        $type = 'config';
        $this->client->request('GET', sprintf('/api/nodes/%s/list_nodes', $type));
        $response = $this->client->getResponse();

        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    private function prepareNodeHandlerMock(): void
    {
        $nodeHandlerMock = self::createMock(NodeHandler::class);
        $nodeHandlerMock
            ->method('getNode')
            ->willThrowException(new LockException('Its lock.'));
        $container = $this->client->getContainer();
        $container->set('hbpf.configurator.handler.node', $nodeHandlerMock);
    }

    /**
     *
     */
    private function prepareNodeMock(): void
    {
        $nodeHandlerMock = $this->getMockBuilder(ConnectorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nodeHandlerMock->method('getConnectors');
    }

}
