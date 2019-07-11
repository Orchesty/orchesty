<?php declare(strict_types=1);

namespace Tests\Integration\Metrics;

use Exception;
use Hanaboso\CommonsBundle\Document\Node;
use Hanaboso\CommonsBundle\Document\Topology;
use Hanaboso\CommonsBundle\Utils\GeneratorUtils;
use Hanaboso\PipesFramework\Metrics\Client\MetricsClient;
use Hanaboso\PipesFramework\Metrics\MetricsManager;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
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
     * @throws Exception
     */
    public function testGetNodeMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getNodeMetrics($node, $topo, []);

        self::assertTrue(is_array($result));
        self::assertCount(6, $result);
        self::assertArrayHasKey(MetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MetricsManager::PROCESS, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyMetrics(): void
    {
        $topo      = $this->createTopo();
        $node      = $this->createNode($topo);
        $nodeTwo   = $this->createNode($topo);
        $nodeThree = $this->createNode($topo);

        $this->setFakeData($topo, $node);
        $this->setFakeData($topo, $nodeTwo);
        $this->setFakeData($topo, $nodeThree);

        $manager = $this->getManager();
        $result  = $manager->getTopologyMetrics($topo, []);

        self::assertTrue(is_array($result));
        self::assertCount(4, $result);
        self::assertArrayHasKey($node->getId(), $result);
        $result = $result[$node->getId()];

        self::assertTrue(is_array($result));
        self::assertCount(6, $result);
        self::assertArrayHasKey(MetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MetricsManager::PROCESS, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyRequestCountMetric(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo, $node);
        $this->setFakeData($topo, $this->createNode($topo));
        $this->setFakeData($topo, $this->createNode($topo));

        $manager = $this->getManager();
        $result  = $manager->getTopologyRequestCountMetrics($topo, [
            'from' => '-10 day',
            'to'   => '+10 day',
        ]);

        self::assertTrue(is_array($result));
        self::assertCount(5, $result);
        self::assertCount(117, $result['requests']);
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
        $host = self::$container->getParameter('influx.host');
        $port = self::$container->getParameter('influx.api_port');
        $user = self::$container->getParameter('influx.user');
        $pass = self::$container->getParameter('influx.password');

        return new MetricsClient($host, $port, $user, $pass, 'test');
    }

    /**
     * @return MetricsManager
     */
    private function getManager(): MetricsManager
    {
        $nodeTable    = self::$container->getParameter('influx.node_table');
        $fpmTable     = self::$container->getParameter('influx.monolith_table');
        $connTable    = self::$container->getParameter('influx.connector_table');
        $rabbitTable  = self::$container->getParameter('influx.rabbit_table');
        $counterTable = self::$container->getParameter('influx.counter_table');

        return new MetricsManager(
            $this->getClient(),
            $this->dm,
            $nodeTable,
            $fpmTable,
            $rabbitTable,
            $counterTable,
            $connTable
        );
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @throws Exception
     */
    private function setFakeData(Topology $topology, Node $node): void
    {
        $client = $this->getClient()->createClient();
        $client->selectDB('test')->drop();
        $client->query('', 'CREATE DATABASE test');
        $client->selectDB('test')->create(new RetentionPolicy('5s', '1h', 1, TRUE));
        $client->selectDB('test')->create(new RetentionPolicy('4h', '4h', 1, TRUE));
        $database = $this->getClient()->getDatabase('test');

        $points = [
            new Point(
                'processes',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                    MetricsManager::NODE     => $node->getId(),
                ],
                [
                    MetricsManager::MAX_WAIT_TIME    => 10,
                    MetricsManager::MIN_WAIT_TIME    => 2,
                    MetricsManager::AVG_WAIT_TIME    => 6,
                    MetricsManager::MAX_PROCESS_TIME => 10,
                    MetricsManager::MIN_PROCESS_TIME => 2,
                    MetricsManager::AVG_PROCESS_TIME => 6,
                    MetricsManager::FAILED_COUNT     => 1,
                ]
            ),
        ];
        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'connector',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                    MetricsManager::NODE     => $node->getId(),
                ],
                [
                    MetricsManager::MAX_TIME => 10,
                    MetricsManager::MIN_TIME => 2,
                    MetricsManager::AVG_TIME => 6,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'monolith',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                    MetricsManager::NODE     => $node->getId(),
                ],
                [
                    MetricsManager::CPU_KERNEL_MAX => 10,
                    MetricsManager::CPU_KERNEL_MIN => 2,
                    MetricsManager::CPU_KERNEL_AVG => 6,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'rabbitmq',
                NULL,
                [
                    MetricsManager::QUEUE => GeneratorUtils::generateQueueName($topology, $node),
                ],
                [
                    MetricsManager::AVG_MESSAGES => 5,
                    MetricsManager::MAX_MESSAGES => 10,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'processes',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                ],
                [
                    MetricsManager::AVG_TIME     => 5,
                    MetricsManager::MIN_TIME     => 5,
                    MetricsManager::MAX_TIME     => 2,
                    MetricsManager::FAILED_COUNT => 10,
                    MetricsManager::TOTAL_COUNT  => 100,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'processes',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                ],
                [
                    MetricsManager::AVG_TIME     => 2,
                    MetricsManager::MIN_TIME     => 1,
                    MetricsManager::MAX_TIME     => 2,
                    MetricsManager::FAILED_COUNT => 10,
                    MetricsManager::TOTAL_COUNT  => 100,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'processes',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                    MetricsManager::NODE     => $node->getId(),
                ],
                [
                    MetricsManager::MAX_WAIT_TIME    => 10,
                    MetricsManager::MIN_WAIT_TIME    => 2,
                    MetricsManager::AVG_WAIT_TIME    => 6,
                    MetricsManager::MAX_PROCESS_TIME => 10,
                    MetricsManager::MIN_PROCESS_TIME => 2,
                    MetricsManager::AVG_PROCESS_TIME => 6,
                    MetricsManager::FAILED_COUNT     => 1,
                ]
            ),
        ];
        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'connector',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                    MetricsManager::NODE     => $node->getId(),
                ],
                [
                    MetricsManager::MAX_TIME => 10,
                    MetricsManager::MIN_TIME => 2,
                    MetricsManager::AVG_TIME => 6,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'monolith',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                    MetricsManager::NODE     => $node->getId(),
                ],
                [
                    MetricsManager::CPU_KERNEL_MAX => 10,
                    MetricsManager::CPU_KERNEL_MIN => 2,
                    MetricsManager::CPU_KERNEL_AVG => 6,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'rabbitmq',
                NULL,
                [
                    MetricsManager::QUEUE => GeneratorUtils::generateQueueName($topology, $node),
                ],
                [
                    MetricsManager::AVG_MESSAGES => 5,
                    MetricsManager::MAX_MESSAGES => 10,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'processes',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                ],
                [
                    MetricsManager::AVG_TIME     => 5,
                    MetricsManager::MIN_TIME     => 2,
                    MetricsManager::MAX_TIME     => 10,
                    MetricsManager::FAILED_COUNT => 10,
                    MetricsManager::TOTAL_COUNT  => 100,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'processes',
                NULL,
                [
                    MetricsManager::TOPOLOGY => $topology->getId(),
                ],
                [
                    MetricsManager::AVG_TIME     => 2,
                    MetricsManager::MIN_TIME     => 1,
                    MetricsManager::MAX_TIME     => 2,
                    MetricsManager::FAILED_COUNT => 10,
                    MetricsManager::TOTAL_COUNT  => 100,
                ]
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
    }

}
