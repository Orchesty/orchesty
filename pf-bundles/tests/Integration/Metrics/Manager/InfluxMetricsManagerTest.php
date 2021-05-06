<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Manager;

use Exception;
use Hanaboso\PipesFramework\Metrics\Client\MetricsClient;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\Utils\System\NodeGeneratorUtils;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
use InfluxDB\Point;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class InfluxMetricsManagerTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Manager
 */
final class InfluxMetricsManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerAbstract
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::runQuery
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::processGraphResult
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::processResultSet
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getPoints
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getPointsFromSerie
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::processInnerResult
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::generateOutput
     *
     * @throws Exception
     */
    public function testGetNodeMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getNodeMetrics($node, $topo, []);

        self::assertCount(6, $result);
        self::assertArrayHasKey(InfluxMetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(InfluxMetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(InfluxMetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(InfluxMetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(InfluxMetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(InfluxMetricsManager::PROCESS, $result);

        self::assertEquals(
            [
                InfluxMetricsManager::QUEUE_DEPTH  => [
                    'max' => '10',
                    'avg' => '5.00',
                ],
                InfluxMetricsManager::WAITING_TIME => [
                    'max' => '10',
                    'avg' => '6.00',
                    'min' => '2',
                ],
                InfluxMetricsManager::PROCESS_TIME => [
                    'max' => '10',
                    'avg' => '6.00',
                    'min' => '2',
                ],
                InfluxMetricsManager::CPU_TIME     => [
                    'max' => '10',
                    'avg' => '6.00',
                    'min' => '2',
                ],
                InfluxMetricsManager::REQUEST_TIME => [
                    'max' => '10',
                    'avg' => '6.00',
                    'min' => '2',
                ],
                InfluxMetricsManager::PROCESS      => [
                    'max'    => '0',
                    'avg'    => '0.00',
                    'min'    => '0',
                    'total'  => '4',
                    'errors' => '2',
                ],
            ],
            $result,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::runQuery
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::processGraphResult
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::processResultSet
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getPoints
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getPointsFromSerie
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::processInnerResult
     *
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

        self::assertCount(4, $result);
        self::assertArrayHasKey($node->getId(), $result);
        self::assertEquals(
            [
                InfluxMetricsManager::PROCESS_TIME => [
                    'min' => '1',
                    'avg' => '3.50',
                    'max' => '10',
                ],
                InfluxMetricsManager::PROCESS      => [
                    'total'  => '1200',
                    'errors' => '120',
                ],
            ],
            $result['topology'],
        );
        $result = $result[$node->getId()];

        self::assertTrue(is_array($result));
        self::assertCount(6, $result);
        self::assertArrayHasKey(InfluxMetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(InfluxMetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(InfluxMetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(InfluxMetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(InfluxMetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(InfluxMetricsManager::PROCESS, $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getTopologyRequestCountMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::runQuery
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getFunctionForSelect
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::addStringSeparator
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::addStringSeparator
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::processGraphResult
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::processResultSet
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::addRetentionPolicy
     * @covers \Hanaboso\PipesFramework\Metrics\Builder\Builder::setTimeRange
     * @covers \Hanaboso\PipesFramework\Metrics\Builder\Builder::parseQuery
     *
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
        $result  = $manager->getTopologyRequestCountMetrics(
            $topo,
            [
                'from' => '-10 day',
                'to'   => '+10 day',
            ],
        );

        self::assertCount(5, $result);
        self::assertEquals(
            [
                InfluxMetricsManager::PROCESS_TIME => [
                    'min' => '1',
                    'avg' => '3.50',
                    'max' => '10',
                ],
                InfluxMetricsManager::PROCESS      => [
                    'total'  => '1200',
                    'errors' => '120',
                ],
            ],
            $result['topology'],
        );
        self::assertCount(117, $result['requests']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getApplicationMetrics
     * @throws Exception
     */
    public function testGetApplicationMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo, $node, 'nutshell');

        $manager = $this->getManager();
        $result  = $manager->getApplicationMetrics(
            [
                'from' => '-10 day',
                'to'   => '+10 day',
            ],
            'nutshell',
        );

        self::assertCount(0, $result['application']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::getUserMetrics
     * @throws Exception
     */
    public function testGetUserMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo, $node, 'nutshell', 'user123');

        $manager = $this->getManager();
        $result  = $manager->getUserMetrics(
            [
                'from' => '-10 day',
                'to'   => '+10 day',
            ],
            'user123',
        );

        self::assertCount(0, $result['user']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager::runQuery
     *
     * @throws Exception
     */
    public function testInfluxMetricsManager(): void
    {
        $manager = $this->getManager();

        self::expectException(MetricsException::class);
        $this->invokeMethod($manager, 'runQuery', ['select', 'from', []]);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $client = $this->getClient()->createClient();
        $client->selectDB('test')->drop();
        $client->selectDB('test')->create(new RetentionPolicy('5s', '1h', 1, TRUE));
        $client->selectDB('test')->create(new RetentionPolicy('4h', '4h', 1, TRUE));
        $client->query('', 'CREATE DATABASE test');
    }

    /**
     * --------------------------------------- HELPERS ----------------------------------
     */

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

    /**
     * @return MetricsClient
     */
    private function getClient(): MetricsClient
    {
        /** @var string $host */
        $host = self::$container->getParameter('influx.host');
        /** @var int $port */
        $port = self::$container->getParameter('influx.api_port');
        /** @var string $user */
        $user = self::$container->getParameter('influx.user');
        /** @var string $pass */
        $pass = self::$container->getParameter('influx.password');

        return new MetricsClient($host, $port, $user, $pass, 'test');
    }

    /**
     * @return InfluxMetricsManager
     */
    private function getManager(): InfluxMetricsManager
    {
        /** @var string $nodeTable */
        $nodeTable = self::$container->getParameter('influx.node_table');
        /** @var string $fpmTable */
        $fpmTable = self::$container->getParameter('influx.monolith_table');
        /** @var string $connTable */
        $connTable = self::$container->getParameter('influx.connector_table');
        /** @var string $rabbitTable */
        $rabbitTable = self::$container->getParameter('influx.rabbit_table');
        /** @var string $counterTable */
        $counterTable = self::$container->getParameter('influx.counter_table');

        return new InfluxMetricsManager(
            $this->getClient(),
            $this->dm,
            $nodeTable,
            $fpmTable,
            $rabbitTable,
            $counterTable,
            $connTable,
        );
    }

    /**
     * @param Topology    $topology
     * @param Node        $node
     * @param string|null $key
     *
     * @param string|null $user
     *
     * @throws Exception
     */
    private function setFakeData(Topology $topology, Node $node, ?string $key = NULL, ?string $user = NULL): void
    {
        $database = $this->getClient()->getDatabase('test');

        $points = [
            new Point(
                'bridges',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                    InfluxMetricsManager::NODE     => $node->getId(),
                ],
                [
                    InfluxMetricsManager::MAX_WAIT_TIME    => 10,
                    InfluxMetricsManager::MIN_WAIT_TIME    => 2,
                    InfluxMetricsManager::AVG_WAIT_TIME    => 6,
                    InfluxMetricsManager::MAX_PROCESS_TIME => 10,
                    InfluxMetricsManager::MIN_PROCESS_TIME => 2,
                    InfluxMetricsManager::AVG_PROCESS_TIME => 6,
                    InfluxMetricsManager::FAILED_COUNT     => 1,
                    InfluxMetricsManager::TOTAL_COUNT      => 2,
                ],
            ),
        ];
        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                    InfluxMetricsManager::NODE     => $node->getId(),
                ],
                [
                    InfluxMetricsManager::MAX_TIME => 10,
                    InfluxMetricsManager::MIN_TIME => 2,
                    InfluxMetricsManager::AVG_TIME => 6,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'monolith',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                    InfluxMetricsManager::NODE     => $node->getId(),
                ],
                [
                    InfluxMetricsManager::CPU_KERNEL_MAX => 10,
                    InfluxMetricsManager::CPU_KERNEL_MIN => 2,
                    InfluxMetricsManager::CPU_KERNEL_AVG => 6,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'rabbitmq',
                NULL,
                [
                    InfluxMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                        $topology->getId(),
                        $node->getId(),
                        $node->getName(),
                    ),
                ],
                [
                    InfluxMetricsManager::AVG_MESSAGES => 5,
                    InfluxMetricsManager::MAX_MESSAGES => 10,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'processes',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                ],
                [
                    InfluxMetricsManager::AVG_TIME     => 5,
                    InfluxMetricsManager::MIN_TIME     => 5,
                    InfluxMetricsManager::MAX_TIME     => 2,
                    InfluxMetricsManager::FAILED_COUNT => 10,
                    InfluxMetricsManager::TOTAL_COUNT  => 100,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'processes',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                ],
                [
                    InfluxMetricsManager::AVG_TIME     => 2,
                    InfluxMetricsManager::MIN_TIME     => 1,
                    InfluxMetricsManager::MAX_TIME     => 2,
                    InfluxMetricsManager::FAILED_COUNT => 10,
                    InfluxMetricsManager::TOTAL_COUNT  => 100,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'bridges',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                    InfluxMetricsManager::NODE     => $node->getId(),
                ],
                [
                    InfluxMetricsManager::MAX_WAIT_TIME    => 10,
                    InfluxMetricsManager::MIN_WAIT_TIME    => 2,
                    InfluxMetricsManager::AVG_WAIT_TIME    => 6,
                    InfluxMetricsManager::MAX_PROCESS_TIME => 10,
                    InfluxMetricsManager::MIN_PROCESS_TIME => 2,
                    InfluxMetricsManager::AVG_PROCESS_TIME => 6,
                    InfluxMetricsManager::FAILED_COUNT     => 1,
                    InfluxMetricsManager::TOTAL_COUNT      => 2,
                ],
            ),
        ];
        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                    InfluxMetricsManager::NODE     => $node->getId(),
                ],
                [
                    InfluxMetricsManager::MAX_TIME => 10,
                    InfluxMetricsManager::MIN_TIME => 2,
                    InfluxMetricsManager::AVG_TIME => 6,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'monolith',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                    InfluxMetricsManager::NODE     => $node->getId(),
                ],
                [
                    InfluxMetricsManager::CPU_KERNEL_MAX => 10,
                    InfluxMetricsManager::CPU_KERNEL_MIN => 2,
                    InfluxMetricsManager::CPU_KERNEL_AVG => 6,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'rabbitmq',
                NULL,
                [
                    InfluxMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                        $topology->getId(),
                        $node->getId(),
                        $node->getName(),
                    ),
                ],
                [
                    InfluxMetricsManager::AVG_MESSAGES => 5,
                    InfluxMetricsManager::MAX_MESSAGES => 10,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'processes',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                ],
                [
                    InfluxMetricsManager::AVG_TIME     => 5,
                    InfluxMetricsManager::MIN_TIME     => 2,
                    InfluxMetricsManager::MAX_TIME     => 10,
                    InfluxMetricsManager::FAILED_COUNT => 10,
                    InfluxMetricsManager::TOTAL_COUNT  => 100,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'processes',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                ],
                [
                    InfluxMetricsManager::AVG_TIME     => 2,
                    InfluxMetricsManager::MIN_TIME     => 1,
                    InfluxMetricsManager::MAX_TIME     => 2,
                    InfluxMetricsManager::FAILED_COUNT => 10,
                    InfluxMetricsManager::TOTAL_COUNT  => 100,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::USER        => $user,
                    InfluxMetricsManager::APPLICATION => $key,
                    InfluxMetricsManager::CORRELATION => '123',
                ],
                [
                    InfluxMetricsManager::APP_COUNT  => 1,
                    InfluxMetricsManager::USER_COUNT => 1,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::USER        => $user,
                    InfluxMetricsManager::APPLICATION => $key,
                    InfluxMetricsManager::CORRELATION => '123',
                ],
                [
                    InfluxMetricsManager::APP_COUNT  => 1,
                    InfluxMetricsManager::USER_COUNT => 1,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);

        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::USER        => $user,
                    InfluxMetricsManager::APPLICATION => $key,
                    InfluxMetricsManager::CORRELATION => '456',
                ],
                [
                    InfluxMetricsManager::AVG_TIME   => 5,
                    InfluxMetricsManager::MIN_TIME   => 2,
                    InfluxMetricsManager::MAX_TIME   => 10,
                    InfluxMetricsManager::APP_COUNT  => 1,
                    InfluxMetricsManager::USER_COUNT => 1,
                ],
            ),
        ];
        $database->writePoints($points, Database::PRECISION_NANOSECONDS);

        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY    => $topology->getId(),
                    InfluxMetricsManager::USER        => $user,
                    InfluxMetricsManager::APPLICATION => $key,
                    InfluxMetricsManager::CORRELATION => '789',
                ],
                [
                    InfluxMetricsManager::AVG_TIME   => 5,
                    InfluxMetricsManager::MIN_TIME   => 2,
                    InfluxMetricsManager::MAX_TIME   => 10,
                    InfluxMetricsManager::APP_COUNT  => 1,
                    InfluxMetricsManager::USER_COUNT => 1,
                ],
            ),
        ];
        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
    }

}
