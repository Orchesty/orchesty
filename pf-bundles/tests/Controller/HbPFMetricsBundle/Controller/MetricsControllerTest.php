<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFMetricsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Exception;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerAbstract;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Throwable;

/**
 * Class MetricsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFMetricsBundle\Controller
 */
#[CoversClass(MetricsController::class)]
#[CoversClass(MetricsHandler::class)]
#[CoversClass(MetricsManagerAbstract::class)]
final class MetricsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testTopologyMetricsAction(): void
    {
        $this->mockMetricsManager(
            'getTopologyMetrics',
            [
                'topology' => [
                    'process' => ['fo' => 'bar'],
                    'process_time' => ['min' => 4, 'avg' => 2, 'max' => 10],
                ],
            ],
        );

        $topo = $this->createTopo();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/topologyMetricsRequest.json',
            [],
            [':id' => $topo->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testTopologyMetricsActionErr(): void
    {
        $this->mockMetricsManager('getTopologyMetrics', new MetricsException());

        $topo = $this->createTopo();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/topologyMetricsErrRequest.json',
            [],
            [':id' => $topo->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testNodeMetricsAction(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->mockMetricsManager('getNodeMetrics', ['node' => ['foo' => 'bar']]);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/nodeMetricsRequest.json',
            [],
            [':topoId' => $topo->getId(), ':nodeId' => $node->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testNodeMetricsActionErr(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);
        $this->mockMetricsManager('getNodeMetrics', new MetricsException());

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/nodeMetricsErrRequest.json',
            [],
            [':topoId' => $topo->getId(), ':nodeId' => $node->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testHealthcheckMetricsAction(): void
    {
        $this->mockMetricsManager('getHealthcheckMetrics', [
            [
                'name' => 'node.123abc.123',
                'service' => 'service',
                'topology' => 'topology',
                'type' => 'queue',
            ],
            [
                'name' => 'neco',
                'type' => 'service',
            ],
        ]);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/healthcheckMetricsRequest.json',
            [],
            [],
        );
    }

    /**
     * @throws Exception
     */
    public function testHealthcheckMetricsActionErr(): void
    {
        $this->mockMetricsManager('getHealthcheckMetrics', new DocumentNotFoundException());

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/healthcheckMetricsErrRequest.json',
            [],
            [],
        );
    }

    /**
     * @throws Exception
     */
    public function testTopologyRequestCount(): void
    {
        $topo = $this->createTopo();
        $this->mockMetricsManager('getTopologyRequestCountMetrics', ['requests' => ['foo' => 'bar']]);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/topologyRequestMetricsCountRequest.json',
            [],
            [':id' => $topo->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testTopologyRequestCountErr(): void
    {
        $topo = $this->createTopo();
        $this->mockMetricsManager('getTopologyRequestCountMetrics', new MetricsException());

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/topologyRequestMetricsCountErrRequest.json',
            [],
            [':id' => $topo->getId()],
        );
    }

    /**
     * @param string $fn
     * @param mixed  $return
     */
    private function mockMetricsManager(string $fn, mixed $return): void
    {
        $manager = self::createPartialMock(MongoMetricsManager::class, [$fn]);

        if ($return instanceof Throwable) {
            $manager->expects(self::any())->method($fn)->willThrowException($return);
        } else {
            $manager->expects(self::any())->method($fn)->willReturn($return);
        }

        self::getContainer()->set('hbpf.metrics.manager.mongo_metrics', $manager);
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
