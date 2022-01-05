<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class NodeControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController
 */
final class NodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::getNodesAction
     *
     * @throws Exception
     */
    public function testGetNodesAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/NodeController/getNodesRequest.json',
            ['_id' => '123456789', 'topology_id' => '123456789'],
            [':id' => $this->createTopology()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::getNodeAction
     *
     * @throws Exception
     */
    public function testGetNodeAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/NodeController/getNodeRequest.json',
            ['_id' => '123456789', 'topology_id' => '123456789'],
            [':id' => $this->createNode()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::updateNodeAction
     *
     * @throws Exception
     */
    public function testUpdateNodeAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/NodeController/updateNodeRequest.json',
            ['_id' => '123456789', 'topology_id' => '123456789'],
            [':id' => $this->createNode()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::listOfNodesAction
     *
     * @throws Exception
     */
    public function testListNodesAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/NodeController/listConnectorNodesRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::listOfNodesAction
     *
     * @throws Exception
     */
    public function testListNodesAction2(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/NodeController/listCustomNodesRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::listOfNodesAction
     */
    public function testListOfNodesEmpty(): void
    {
        $nodeController = self::getContainer()->get(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController',
        );

        $result = $nodeController->listOfNodesAction('something');
        self::assertEquals(200, $result->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NodeController::listNodesNamesAction
     *
     * @throws Exception
     */
    public function testListNodesNamesAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/NodeController/listNodesNamesRequest.json');
    }

    /**
     * @return Topology
     * @throws Exception
     */
    private function createTopology(): Topology
    {
        $topology = new Topology();

        $this->pfd($topology);
        $this->createNode()->setTopology($topology->getId());
        $this->dm->flush();

        return $topology;
    }

    /**
     * @return Node
     * @throws Exception
     */
    private function createNode(): Node
    {
        $node = new Node();
        $node->setTopology('1');

        $this->pfd($node);

        return $node;
    }

}
