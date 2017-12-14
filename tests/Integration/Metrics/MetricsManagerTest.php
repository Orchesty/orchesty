<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 15:49
 */

namespace Tests\Integration\Metrics;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Client\MetricsClient;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\MetricsManager;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
use InfluxDB\Exception;
use InfluxDB\Point;
use Tests\KernelTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class MetricsManagerTest
 *
 * @package Tests\Integration\Metrics
 */
final class MetricsManagerTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Database\Exception
     * @throws Exception
     * @throws MetricsException
     */
    public function testGetNodeMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo->getId(), $node->getId());

        $manager = $this->getManager();
        $result  = $manager->getNodeMetrics($node, []);

        self::assertTrue(is_array($result));
        self::assertCount(6, $result);
        self::assertArrayHasKey(MetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MetricsManager::ERROR, $result);
    }

    /**
     * @throws Database\Exception
     * @throws Exception
     * @throws MetricsException
     */
    public function testGetTopologyMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo->getId(), $node->getId());

        $manager = $this->getManager();
        $result  = $manager->getTopologyMetrics($topo, []);

        self::assertTrue(is_array($result));
        self::assertCount(1, $result);
        self::assertArrayHasKey($node->getId(), $result);
        $result = $result[$node->getId()];

        self::assertTrue(is_array($result));
        self::assertCount(6, $result);
        self::assertArrayHasKey(MetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MetricsManager::ERROR, $result);
    }

    /**
     * --------------------------------------- HELPERS ----------------------------------
     */

    /**
     * @return Topology
     */
    private function createTopo(): Topology
    {
        $topo = new Topology();
        $topo->setName(uniqid());
        $this->dm->persist($topo);
        $this->dm->flush($topo);

        return $topo;
    }

    /**
     * @param Topology $topology
     *
     * @return Node
     */
    private function createNode(Topology $topology): Node
    {
        $node = new Node();
        $node
            ->setTopology($topology->getId())
            ->setName(uniqid());
        $this->dm->persist($node);
        $this->dm->flush($node);

        return $node;
    }

    /**
     * @return MetricsClient
     */
    private function getClient(): MetricsClient
    {
        $host = $this->container->getParameter('influx.host');
        $port = $this->container->getParameter('influx.port');
        $user = $this->container->getParameter('influx.user');
        $pass = $this->container->getParameter('influx.password');

        return new MetricsClient($host, $port, $user, $pass, 'test');
    }

    /**
     * @return MetricsManager
     */
    private function getManager(): MetricsManager
    {
        $table = $this->container->getParameter('influx.table');

        return new MetricsManager($this->getClient(), $this->dm, $table);
    }

    /**
     * @param string $topologyId
     * @param string $nodeId
     *
     * @throws Database\Exception
     * @throws Exception
     * @throws MetricsException
     */
    private function setFakeData(string $topologyId, string $nodeId): void
    {
        $this->getClient()->createClient()->selectDB('test')->create(new RetentionPolicy('test', '1d', 1, TRUE));
        $database = $this->getClient()->getDatabase('test');
        $points   = [
            new Point(
                'pipes_node',
                NULL,
                [
                    MetricsManager::NODE     => $nodeId,
                    MetricsManager::TOPOLOGY => $topologyId,
                ],
                [
                    MetricsManager::WAIT_TIME          => 10,
                    MetricsManager::REQUEST_TOTAL_TIME => 10,
                    MetricsManager::CPU_KERNEL_TIME    => 10,
                    MetricsManager::NODE_PROCESS_TIME  => 10,
                ]
            ),
        ];
        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'pipes_node',
                NULL,
                [
                    MetricsManager::NODE     => $nodeId,
                    MetricsManager::TOPOLOGY => $topologyId,
                ],
                [
                    MetricsManager::WAIT_TIME          => 2,
                    MetricsManager::REQUEST_TOTAL_TIME => 2,
                    MetricsManager::CPU_KERNEL_TIME    => 2,
                    MetricsManager::NODE_PROCESS_TIME  => 2,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
    }

}