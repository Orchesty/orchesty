<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFMetricsBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Throwable;

/**
 * Class MetricsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFMetricsBundle\Controller
 */
final class MetricsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::topologyMetricsAction
     *
     * @throws Exception
     */
    public function testTopologyMetricsAction(): void
    {
        $this->mockMetricsManager(
            'getTopologyMetrics',
            [
                'topology' => [
                    'process_time' => ['min' => 4, 'avg' => 2, 'max' => 10], 'process' => ['fo' => 'bar'],
                ],
            ],
        );

        $topo = $this->createTopo();
        $this->assertResponse(__DIR__ . '/data/topologyMetricsRequest.json', [], [':id' => $topo->getId()]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::topologyMetricsAction
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getTopologyMetrics
     *
     * @throws Exception
     */
    public function testTopologyMetricsActionErr(): void
    {
        $this->mockMetricsManager('getTopologyMetrics', new MetricsException());

        $topo = $this->createTopo();
        $this->assertResponse(__DIR__ . '/data/topologyMetricsErrRequest.json', [], [':id' => $topo->getId()]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::nodeMetricsAction
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getNodeByTopologyAndNodeId
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testNodeMetricsAction(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->mockMetricsManager('getNodeMetrics', ['node' => ['foo' => 'bar']]);

        $this->assertResponse(
            __DIR__ . '/data/nodeMetricsRequest.json',
            [],
            [':topoId' => $topo->getId(), ':nodeId' => $node->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::nodeMetricsAction
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getNodeByTopologyAndNodeId
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testNodeMetricsActionErr(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);
        $this->mockMetricsManager('getNodeMetrics', new MetricsException());

        $this->assertResponse(
            __DIR__ . '/data/nodeMetricsErrRequest.json',
            [],
            [':topoId' => $topo->getId(), ':nodeId' => $node->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::topologyRequestsCountMetricsAction
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getRequestsCountMetrics
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testTopologyRequestCount(): void
    {
        $topo = $this->createTopo();
        $this->mockMetricsManager('getTopologyRequestCountMetrics', ['requests' => ['foo' => 'bar']]);

        $this->assertResponse(
            __DIR__ . '/data/topologyRequestMetricsCountRequest.json',
            [],
            [':id' => $topo->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::topologyRequestsCountMetricsAction
     *
     * @throws Exception
     */
    public function testTopologyRequestCountErr(): void
    {
        $topo = $this->createTopo();
        $this->mockMetricsManager('getTopologyRequestCountMetrics', new MetricsException());

        $this->assertResponse(
            __DIR__ . '/data/topologyRequestMetricsCountErrRequest.json',
            [],
            [':id' => $topo->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::applicationMetricsAction
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getApplicationMetrics
     *
     * @throws Exception
     */
    public function testApplicationMetrics(): void
    {
        $this->mockMetricsManager('getApplicationMetrics', ['count' => 5]);

        $this->assertResponse(
            __DIR__ . '/data/applicationMetricsRequest.json',
            [],
            [':key' => 'superApp'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::applicationMetricsAction
     *
     * @throws Exception
     */
    public function testApplicationErr(): void
    {
        $this->mockMetricsManager('getApplicationMetrics', new Exception());

        $this->assertResponse(
            __DIR__ . '/data/applicationMetricsErrRequest.json',
            [],
            [':key' => 'superApp'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::userMetricsAction
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler::getUserMetrics
     *
     * @throws Exception
     */
    public function testUserMetrics(): void
    {
        $this->mockMetricsManager('getUserMetrics', ['count' => 3]);

        $this->assertResponse(
            __DIR__ . '/data/userMetricsRequest.json',
            [],
            [':user' => '123-456-789'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::userMetricsAction
     *
     * @throws Exception
     */
    public function testUserErr(): void
    {
        $this->mockMetricsManager('getUserMetrics', new Exception());

        $this->assertResponse(
            __DIR__ . '/data/userMetricsErrRequest.json',
            [],
            [':key' => 'superApp'],
        );
    }

    /**
     * @param string $fn
     * @param mixed  $return
     */
    private function mockMetricsManager(string $fn, $return): void
    {
        $manager = self::createPartialMock(InfluxMetricsManager::class, [$fn]);

        if ($return instanceof Throwable) {
            $manager->expects(self::any())->method($fn)->willThrowException($return);
        } else {
            $manager->expects(self::any())->method($fn)->willReturn($return);
        }

        self::$container->set('hbpf.metrics.manager.influx_metrics', $manager);
    }

    /**
     * @return Topology
     * @throws Exception
     */
    private function createTopo(): Topology
    {
        $topo = new Topology();
        $topo->setName(uniqid());
        $this->dm->persist($topo);
        $this->dm->flush();

        return $topo;
    }

    /**
     * @param Topology $topology
     *
     * @return Node
     * @throws Exception
     */
    private function createNode(Topology $topology): Node
    {
        $node = new Node();
        $node
            ->setTopology($topology->getId())
            ->setName(uniqid());
        $this->dm->persist($node);
        $this->dm->flush();

        return $node;
    }

}
