<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\MetricsController;
use Hanaboso\PipesFramework\Metrics\Enum\ServiceNameByQueueEnum;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerAbstract;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class MetricsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(MetricsController::class)]
#[CoversClass(MetricsManagerAbstract::class)]
#[CoversClass(ServiceNameByQueueEnum::class)]
#[CoversClass(MongoMetricsManager::class)]
final class MetricsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testTopologyMetricsAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/MetricsController/topologyMetricsRequest.json',
            [],
            [':id' => $this->createTopology()->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testNodeMetricsAction(): void
    {
        $topologyId = $this->createTopology()->getId();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/MetricsController/nodeMetricsRequest.json',
            [],
            [':topologyId' => $topologyId, ':nodeId' => $this->createNode($topologyId)->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testTopologyRequestsCountMetricsAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/MetricsController/topologyRequestsCountMetricsRequest.json',
            [],
            [':id' => $this->createTopology()->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testHealthcheckMetricsAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/MetricsController/healthcheckMetricsRequest.json',
            [],
            [],
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
