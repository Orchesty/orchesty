<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class MetricsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\MetricsController
 */
final class MetricsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\MetricsController::topologyMetricsAction
     *
     * @throws Exception
     */
    public function testTopologyMetricsAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/MetricsController/topologyMetricsRequest.json',
            [],
            [':id' => $this->createTopology()->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\MetricsController::nodeMetricsAction
     *
     * @throws Exception
     */
    public function testNodeMetricsAction(): void
    {
        $topologyId = $this->createTopology()->getId();

        $this->assertResponse(
            __DIR__ . '/data/MetricsController/nodeMetricsRequest.json',
            [],
            [':topologyId' => $topologyId, ':nodeId' => $this->createNode($topologyId)->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\MetricsController::topologyRequestsCountMetricsAction
     *
     * @throws Exception
     */
    public function testTopologyRequestsCountMetricsAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/MetricsController/topologyRequestsCountMetricsRequest.json',
            [],
            [':id' => $this->createTopology()->getId()],
        );
    }

    /**
     * @return Topology
     * @throws Exception
     */
    private function createTopology(): Topology
    {
        $topology = new Topology();

        $this->pfd($topology);

        return $topology;
    }

    /**
     * @param string $topology
     *
     * @return Node
     * @throws Exception
     */
    private function createNode(string $topology): Node
    {
        $node = (new Node())->setTopology($topology);

        $this->pfd($node);

        return $node;
    }

}