<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\NodeGeneratorUtils;
use LogicException;
use MongoDB\BSON\UTCDateTime;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use PipesFrameworkTests\DataProvider;
use Throwable;

/**
 * Class MongoMetricsManagerTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Manager
 */
#[CoversClass(MongoMetricsManager::class)]
final class MongoMetricsManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetNodeMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo, $node);

        $manager = $this->getManager();
        $manager->setLogger(new Logger('logger'));

        $result = $manager->getNodeMetrics(
            $node,
            $topo,
            [
                'from' => '-2 days',
                'to'   => '+2 days',
            ],
        );

        self::assertCount(6, $result);
        self::assertArrayHasKey(MongoMetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MongoMetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS, $result);

        self::assertEquals(
            [
                MongoMetricsManager::CPU_TIME     => [
                    'avg' => '10.00',
                    'max' => '15',
                    'min' => '5',
                ],
                MongoMetricsManager::PROCESS      => [
                    'avg'    => '0.00',
                    'errors' => '1',
                    'max'    => '0',
                    'min'    => '0',
                    'total'  => '2',
                ],
                MongoMetricsManager::PROCESS_TIME => [
                    'avg' => '7.00',
                    'max' => '10',
                    'min' => '4',
                ],
                MongoMetricsManager::QUEUE_DEPTH  => [
                    'avg' => '0.00',
                    'max' => '0',
                ],
                MongoMetricsManager::REQUEST_TIME => [
                    'avg' => '10.00',
                    'max' => '15',
                    'min' => '5',
                ],
                MongoMetricsManager::WAITING_TIME => [
                    'avg' => '2.50',
                    'max' => '5',
                    'min' => '0',
                ],
            ],
            $result,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetNodeMetricsSingleDocument(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setMinimalFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getNodeMetrics(
            $node,
            $topo,
            [
                'from' => '-2 days',
                'to'   => '+2 days',
            ],
        );

        self::assertCount(6, $result);
        self::assertArrayHasKey(MongoMetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MongoMetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS, $result);

        self::assertEquals(
            [
                MongoMetricsManager::CPU_TIME     => [
                    'avg' => '15.00',
                    'max' => '15',
                    'min' => '15',
                ],
                MongoMetricsManager::PROCESS      => [
                    'avg'    => '0.00',
                    'errors' => '0',
                    'max'    => '0',
                    'min'    => '0',
                    'total'  => '1',
                ],
                MongoMetricsManager::PROCESS_TIME => [
                    'avg' => '10.00',
                    'max' => '10',
                    'min' => '10',
                ],
                MongoMetricsManager::QUEUE_DEPTH  => [
                    'avg' => '0.00',
                    'max' => '0',
                ],
                MongoMetricsManager::REQUEST_TIME => [
                    'avg' => '5.00',
                    'max' => '5',
                    'min' => '5',
                ],
                MongoMetricsManager::WAITING_TIME => [
                    'avg' => '5.00',
                    'max' => '5',
                    'min' => '5',
                ],
            ],
            $result,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetNodeMetricsNoDocument(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $manager = $this->getManager();
        $result  = $manager->getNodeMetrics(
            $node,
            $topo,
            [
                'from' => '-2 days',
                'to'   => '+2 days',
            ],
        );

        self::assertCount(5, $result);
        self::assertArrayHasKey(MongoMetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MongoMetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS, $result);

        self::assertEquals(
            [
                MongoMetricsManager::CPU_TIME     => [
                    'avg' => '0.00',
                    'max' => '0',
                    'min' => '0',
                ],
                MongoMetricsManager::PROCESS      => [
                    'avg'    => '0.00',
                    'errors' => '0',
                    'max'    => '0',
                    'min'    => '0',
                    'total'  => '0',
                ],
                MongoMetricsManager::PROCESS_TIME => [
                    'avg' => '0.00',
                    'max' => '0',
                    'min' => '0',
                ],
                MongoMetricsManager::QUEUE_DEPTH  => [
                    'avg' => '0.00',
                    'max' => '0',
                ],
                MongoMetricsManager::WAITING_TIME => [
                    'avg' => '0.00',
                    'max' => '0',
                    'min' => '0',
                ],
            ],
            $result,
        );
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
        $result  = $manager->getTopologyMetrics(
            $topo,
            [
                'from' => '-2 days',
                'to'   => '+2 days',
            ],
        );

        self::assertCount(4, $result);
        self::assertEquals(
            [
                MongoMetricsManager::PROCESS      => [
                    'errors' => '6',
                    'total'  => '6',
                ],
                MongoMetricsManager::PROCESS_TIME => [
                    'avg' => '2.00',
                    'max' => '2',
                    'min' => '2',
                ],
            ],
            $result['topology'],
        );
        self::assertArrayHasKey($node->getId(), $result);
        $result = $result[$node->getId()];

        self::assertCount(6, $result);
        self::assertArrayHasKey(MongoMetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MongoMetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyMetricsSingleDocument(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setMinimalFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getTopologyMetrics(
            $topo,
            [
                'from' => '-2 days',
                'to'   => '+2 days',
            ],
        );

        self::assertCount(2, $result);
        self::assertEquals(
            [
                MongoMetricsManager::PROCESS      => [
                    'errors' => '1',
                    'total'  => '1',
                ],
                MongoMetricsManager::PROCESS_TIME => [
                    'avg' => '2.00',
                    'max' => '2',
                    'min' => '2',
                ],
            ],
            $result['topology'],
        );
        self::assertArrayHasKey($node->getId(), $result);
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyMetricsNoDocument(): void
    {
        $topo = $this->createTopo();

        $manager = $this->getManager();
        $result  = $manager->getTopologyMetrics(
            $topo,
            [
                'from' => '-2 days',
                'to'   => '+2 days',
            ],
        );

        self::assertCount(1, $result);
        self::assertEquals(
            [
                MongoMetricsManager::PROCESS      => [
                    'errors' => '0',
                    'total'  => '0',
                ],
                MongoMetricsManager::PROCESS_TIME => [
                    'avg' => '0.00',
                    'max' => '0',
                    'min' => '0',
                ],
            ],
            $result['topology'],
        );

        self::expectException(LogicException::class);
        $manager->getTopologyMetrics($topo, []);
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyRequestCountMetric(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo, $node);
        $this->setFakeData($topo, $this->createNode($topo), '+2 days');
        $this->setFakeData($topo, $this->createNode($topo), '+2 days');

        $manager = $this->getManager();
        $result  = $manager->getTopologyRequestCountMetrics(
            $topo,
            [
                'from' => '-10 day',
                'to'   => '+10 day',
            ],
        );

        self::assertCount(5, $result);
        self::assertCount(121, $result['requests']);
        self::assertEquals(6, array_sum($result['requests']));
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyRequestCountMetricSingleDocument(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setMinimalFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getTopologyRequestCountMetrics(
            $topo,
            [
                'from' => '-10 day',
                'to'   => '+10 day',
            ],
        );

        self::assertCount(3, $result);
        self::assertCount(121, $result['requests']);
        self::assertEquals(1, array_sum($result['requests']));
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyRequestCountMetricNoDocument(): void
    {
        $topo = $this->createTopo();
        $this->createNode($topo);

        $manager = $this->getManager();
        $result  = $manager->getTopologyRequestCountMetrics(
            $topo,
            [
                'from' => '-10 day',
                'to'   => '+10 day',
            ],
        );

        self::assertCount(2, $result);
    }

    /**
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetApplicationMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);
        $this->setFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getApplicationMetrics(
            [
                'from' => '-10 day',
                'to'   => '+10 day',
            ],
            'nutshell',
        );
        self::assertEquals(2, $result['application']);
    }

    /**
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetTopologiesProcessTimeMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);
        $this->setFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getTopologiesProcessTimeMetrics(
            [
                'from' => '-10 day',
                'to'   => '+10 day',
            ],
        );
        self::assertEquals(DataProvider::topologiesProcessTimeMetrics(), $result['process']);
    }

    /**
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetUserMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);
        $this->setFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getUserMetrics(
            [
                'from' => '-10 day',
                'to'   => '+10 day',
            ],
            'user1',
        );
        self::assertEquals(2, $result['user']);
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsConnectorsOverviewAggregatedSumsHourlyBuckets(): void
    {
        $this->seedHourlyConnectorAggregate('-3 hours', 100, 2_000, status400: 5, status500: 3);
        $this->seedHourlyConnectorAggregate('-2 hours', 100, 4_000, status400: 1, status500: 2, lastResponseCode: 500);

        $manager = $this->getManager();
        $manager->setLogger(new Logger('logger'));

        $result = $manager->getMetricsConnectorsOverview($this->createdGteDto('-1 days'), TRUE);

        self::assertCount(1, $result);
        self::assertEquals('node-1', $result[0]['nodeId']);
        self::assertEquals('topo-1', $result[0]['topologyId']);
        self::assertEquals('app-1', $result[0]['applicationId']);
        self::assertEquals(200, $result[0]['count']);
        self::assertEquals(6, $result[0]['status400']);
        self::assertEquals(5, $result[0]['status500']);
        self::assertEquals(30, $result[0]['duration']);
        self::assertEquals(500, $result[0]['lastStatus']);
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsConnectorsOverviewAggregatedFallsBackToRawForShortWindow(): void
    {
        $this->seedHourlyConnectorAggregate('-10 minutes', 50, 1_000);

        $manager = $this->getManager();
        $manager->setLogger(new Logger('logger'));

        $result = $manager->getMetricsConnectorsOverview($this->createdGteDto('-30 minutes'), TRUE);

        self::assertSame([], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsConnectorsOverviewAggregatedUsesInclusiveUpperBucketBound(): void
    {
        $this->seedHourlyConnectorAggregate('2026-06-17T10:00:00Z', 100, 1_000);
        $this->seedHourlyConnectorAggregate('2026-06-17T11:00:00Z', 100, 1_000);
        $this->seedHourlyConnectorAggregate('2026-06-17T12:00:00Z', 100, 1_000);

        $manager = $this->getManager();
        $manager->setLogger(new Logger('logger'));

        $result = $manager->getMetricsConnectorsOverview(
            $this->createdBetweenDto('2026-06-17T10:00:00Z', '2026-06-17T12:00:00Z'),
            TRUE,
        );

        self::assertCount(1, $result);
        self::assertEquals(300, $result[0]['count']);
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsRequestsAveragesFromRollup(): void
    {
        $this->seedHourlyConnectorAggregate('2026-06-17T10:00:00Z', 10, 1_000, nodeId: 'node-1', topologyId: 'topo-1');
        $this->seedHourlyConnectorAggregate('2026-06-17T11:00:00Z', 30, 6_000, nodeId: 'node-1', topologyId: 'topo-1');
        $this->seedHourlyConnectorAggregate('2026-06-17T10:00:00Z', 10, 500, nodeId: 'node-2', topologyId: 'topo-1');
        $this->seedHourlyConnectorAggregate('2026-06-17T10:00:00Z', 10, 9_999, nodeId: 'node-3', topologyId: 'topo-2');

        $filter = self::getContainer()->get('hbpf.metric-hourly-request.aggregation-filter');
        $result = $filter->getData($this->topologyIdDto('topo-1'))->toArray();

        self::assertCount(2, $result);
        self::assertSame('node-1', $result[0]['nodeId']);
        self::assertSame('topo-1', $result[0]['topologyId']);
        self::assertEquals(175, $result[0]['duration']);
        self::assertSame('node-2', $result[1]['nodeId']);
        self::assertEquals(50, $result[1]['duration']);
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsProcessesAveragesFromRollup(): void
    {
        $this->seedHourlyPipesNodeAggregate('2026-06-17T10:00:00Z', 10, 1_000, 'node-1', 'topo-1');
        $this->seedHourlyPipesNodeAggregate('2026-06-17T11:00:00Z', 30, 6_000, 'node-1', 'topo-1');
        $this->seedHourlyPipesNodeAggregate('2026-06-17T10:00:00Z', 10, 500, 'node-2', 'topo-1');
        $this->seedHourlyPipesNodeAggregate('2026-06-17T10:00:00Z', 10, 9_999, 'node-3', 'topo-2');

        $filter = self::getContainer()->get('hbpf.metric-hourly-process.aggregation-filter');
        $result = $filter->getData($this->topologyIdDto('topo-1'))->toArray();

        self::assertCount(2, $result);
        self::assertSame('node-1', $result[0]['nodeId']);
        self::assertSame('topo-1', $result[0]['topologyId']);
        self::assertEquals(175, $result[0]['duration']);
        self::assertSame('node-2', $result[1]['nodeId']);
        self::assertEquals(50, $result[1]['duration']);
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsRequestsAveragesFromRaw(): void
    {
        $this->seedConnectorMetric('-10 minutes', 100, 'node-1', 'topo-1');
        $this->seedConnectorMetric('-20 minutes', 300, 'node-1', 'topo-1');
        $this->seedConnectorMetric('-10 minutes', 50, 'node-2', 'topo-1');
        $this->seedConnectorMetric('-10 minutes', 9_999, 'node-3', 'topo-2');

        $manager = $this->getManager();
        $manager->setLogger(new Logger('logger'));

        $result = $manager->getMetricsRequests($this->topologyIdDto('topo-1'), FALSE);

        self::assertCount(2, $result);
        self::assertSame('node-1', $result[0]['nodeId']);
        self::assertSame('topo-1', $result[0]['topologyId']);
        self::assertEquals(200, $result[0]['duration']);
        self::assertSame('node-2', $result[1]['nodeId']);
        self::assertEquals(50, $result[1]['duration']);
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsProcessesAveragesFromRaw(): void
    {
        $this->seedPipesNodeMetric('-10 minutes', 100, 'node-1', 'topo-1');
        $this->seedPipesNodeMetric('-20 minutes', 300, 'node-1', 'topo-1');
        $this->seedPipesNodeMetric('-10 minutes', 50, 'node-2', 'topo-1');
        $this->seedPipesNodeMetric('-10 minutes', 9_999, 'node-3', 'topo-2');

        $manager = $this->getManager();
        $manager->setLogger(new Logger('logger'));

        $result = $manager->getMetricsProcesses($this->topologyIdDto('topo-1'), FALSE);

        self::assertCount(2, $result);
        self::assertSame('node-1', $result[0]['nodeId']);
        self::assertSame('topo-1', $result[0]['topologyId']);
        self::assertEquals(200, $result[0]['duration']);
        self::assertSame('node-2', $result[1]['nodeId']);
        self::assertEquals(50, $result[1]['duration']);
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsConnectorsHeatmapAggregatedSumsBuckets(): void
    {
        $this->seedHourlyConnectorAggregate('2026-06-17T10:00:00Z', 11, 0, status200: 10, status400: 1);
        $this->seedHourlyConnectorAggregate('2026-06-17T10:15:00Z', 22, 0, status200: 20, status500: 2);
        $this->seedHourlyConnectorAggregate('2026-06-17T10:30:00Z', 33, 0, status200: 30, status400: 3);

        $manager = $this->getManager();
        $manager->setLogger(new Logger('logger'));

        $result = $manager->getMetricsConnectorsHeatmap(
            $this->createdBetweenDto('2026-06-17T00:00:00Z', '2026-06-18T00:00:00Z'),
            40,
            TRUE,
        );

        self::assertEquals(60, array_sum(array_column($result, 'success')));
        self::assertEquals(6, array_sum(array_column($result, 'failed')));
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsConnectorsGraphAggregatedSumsBuckets(): void
    {
        $this->seedHourlyConnectorAggregate('2026-06-17T10:00:00Z', 11, 0, status200: 10, status400: 1);
        $this->seedHourlyConnectorAggregate('2026-06-17T10:15:00Z', 22, 0, status200: 20, status500: 2);
        $this->seedHourlyConnectorAggregate('2026-06-17T10:30:00Z', 33, 0, status200: 30, status400: 3);

        $manager = $this->getManager();
        $manager->setLogger(new Logger('logger'));

        $result = $manager->getMetricsConnectorsGraph(
            $this->createdBetweenDto('2026-06-17T00:00:00Z', '2026-06-18T00:00:00Z'),
            40,
            TRUE,
        );

        self::assertEquals(60, array_sum(array_column($result, 'status200')));
        self::assertEquals(4, array_sum(array_column($result, 'status400')));
        self::assertEquals(2, array_sum(array_column($result, 'status500')));
    }

    /**
     * @throws Exception
     */
    public function testGetMetricsConnectorsHeatmapAggregatedFallsBackToRawForNarrowBins(): void
    {
        $this->seedHourlyConnectorAggregate('2026-06-17T10:00:00Z', 11, 0, status200: 10, status400: 1);

        $manager = $this->getManager();
        $manager->setLogger(new Logger('logger'));

        $result = $manager->getMetricsConnectorsHeatmap(
            $this->createdBetweenDto('2026-06-17T10:00:00Z', '2026-06-17T11:00:00Z'),
            40,
            TRUE,
        );

        self::assertSame([], $result);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->ensureCollections();
        } catch (Throwable $t) {
            $t;
            //
        }
    }

    /*
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
     * @return MongoMetricsManager
     */
    private function getManager(): MongoMetricsManager
    {
        return self::getContainer()->get('hbpf.metrics.manager.mongo_metrics');
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     * @param string   $dateOffset
     *
     * @throws Exception
     */
    private function setFakeData(Topology $topology, Node $node, string $dateOffset = '-1 days'): void
    {
        $client = $this->getMdm()->getClient();
        $this->setMinimalFakeData($topology, $node);

        /** @var string $counterTable */
        $counterTable = self::getContainer()->getParameter('mongodb.counter_table');
        /** @var string $monolithTable */
        $monolithTable = self::getContainer()->getParameter('mongodb.monolith_table');
        /** @var string $nodeTable */
        $nodeTable = self::getContainer()->getParameter('mongodb.node_table');
        /** @var string $connectorTable */
        $connectorTable = self::getContainer()->getParameter('mongodb.connector_table');
        /** @var string $rabbitTable */
        $rabbitTable = self::getContainer()->getParameter('mongodb.rabbit_table');
        /** @var string $rabbitConsumerTable */
        $rabbitConsumerTable = self::getContainer()->getParameter('mongodb.rabbit_consumer_table');

        $processes        = $client->selectCollection('metrics', $counterTable);
        $monolith         = $client->selectCollection('metrics', $monolithTable);
        $bridge           = $client->selectCollection('metrics', $nodeTable);
        $connector        = $client->selectCollection('metrics', $connectorTable);
        $rabbitmq         = $client->selectCollection('metrics', $rabbitTable);
        $rabbitmqConsumer = $client->selectCollection('metrics', $rabbitConsumerTable);

        $doc = [
            'fields' => [
                'created'        => new UTCDateTime(DateTimeUtils::getUtcDateTime('-1 days')),
                'result_success' => FALSE,
                'total_duration' => 4,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $bridge->insertOne($doc);

        $doc = [
            'fields' => [
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime('-1 days')),
                'messages' => 5,
            ],
            'tags'   => [
                MongoMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                    $topology->getId(),
                    $node->getId(),
                    $node->getName(),
                ),
            ],
        ];
        $rabbitmq->insertOne($doc);

        $doc = [
            'fields' => [
                'created'                     => new UTCDateTime(DateTimeUtils::getUtcDateTime('-1 days')),
                'sent_request_total_duration' => 15,
            ],
            'tags'   => [
                MongoMetricsManager::APPLICATION => 'nutshell',
                MongoMetricsManager::CORRELATION => '456-789',
                MongoMetricsManager::NODE        => $node->getId(),
                MongoMetricsManager::TOPOLOGY    => $topology->getId(),
                MongoMetricsManager::USER        => 'user2',
            ],
        ];
        $connector->insertOne($doc);

        $doc = [
            'fields' => [
                'created'             => new UTCDateTime(DateTimeUtils::getUtcDateTime('-1 days')),
                'fpm_cpu_kernel_time' => 5,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $monolith->insertOne($doc);

        $doc = [
            'fields' => [
                'created'          => new UTCDateTime(DateTimeUtils::getUtcDateTime('+5 days')),
                'result_success'   => TRUE,
                'total_duration'   => 10,
                'waiting_duration' => 5,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $bridge->insertOne($doc);

        $doc = [
            'fields' => [
                'created'        => new UTCDateTime(DateTimeUtils::getUtcDateTime('-51 days')),
                'result_success' => FALSE,
                'total_duration' => 4,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $bridge->insertOne($doc);

        $doc = [
            'fields' => [
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime('+5 days')),
                'messages' => 3,
            ],
            'tags'   => [
                MongoMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                    $topology->getId(),
                    $node->getId(),
                    $node->getName(),
                ),
            ],
        ];
        $rabbitmq->insertOne($doc);

        $doc = [
            'fields' => [
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime('-6 days')),
                'messages' => 5,
            ],
            'tags'   => [
                MongoMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                    $topology->getId(),
                    $node->getId(),
                    $node->getName(),
                ),
            ],
        ];
        $rabbitmq->insertOne($doc);

        $doc = [
            'fields' => [
                'created'                     => new UTCDateTime(DateTimeUtils::getUtcDateTime('+5 days')),
                'sent_request_total_duration' => 5,
            ],
            'tags'   => [
                MongoMetricsManager::APPLICATION => 'nutshell',
                MongoMetricsManager::CORRELATION => '456-789',
                MongoMetricsManager::NODE        => $node->getId(),
                MongoMetricsManager::TOPOLOGY    => $topology->getId(),
                MongoMetricsManager::USER        => 'user1',
            ],
        ];
        $connector->insertOne($doc);

        $doc = [
            'fields' => [
                'created'                     => new UTCDateTime(DateTimeUtils::getUtcDateTime('-5 days')),
                'sent_request_total_duration' => 15,
            ],
            'tags'   => [
                MongoMetricsManager::APPLICATION => 'nutshell',
                MongoMetricsManager::CORRELATION => '123-456',
                MongoMetricsManager::NODE        => $node->getId(),
                MongoMetricsManager::TOPOLOGY    => $topology->getId(),
                MongoMetricsManager::USER        => 'user1',
            ],
        ];
        $connector->insertOne($doc);

        $doc = [
            'fields' => [
                'created'             => new UTCDateTime(DateTimeUtils::getUtcDateTime('+5 days')),
                'fpm_cpu_kernel_time' => 15,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $monolith->insertOne($doc);

        $doc = [
            'fields' => [
                'created'             => new UTCDateTime(DateTimeUtils::getUtcDateTime('-5 days')),
                'fpm_cpu_kernel_time' => 5,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $monolith->insertOne($doc);

        $doc = [
            'fields' => [
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime($dateOffset)),
                'duration' => 2,
                'result'   => FALSE,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $processes->insertOne($doc);

        $doc = [
            'fields' => [
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime('+55 days')),
                'duration' => 2,
                'result'   => FALSE,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $processes->insertOne($doc);

        $doc = [
            'fields' => [
                'created' => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
            ],
            'tags'   => [
                'consumers' => 0,
                'queue'     => 'pipes.limiter',
            ],
        ];
        $rabbitmqConsumer->insertOne($doc);
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @throws Exception
     */
    private function setMinimalFakeData(Topology $topology, Node $node): void
    {
        $client = $this->getMdm()->getClient();

        /** @var string $counterTable */
        $counterTable = self::getContainer()->getParameter('mongodb.counter_table');
        /** @var string $monolithTable */
        $monolithTable = self::getContainer()->getParameter('mongodb.monolith_table');
        /** @var string $nodeTable */
        $nodeTable = self::getContainer()->getParameter('mongodb.node_table');
        /** @var string $connectorTable */
        $connectorTable = self::getContainer()->getParameter('mongodb.connector_table');
        /** @var string $rabbitTable */
        $rabbitTable = self::getContainer()->getParameter('mongodb.rabbit_table');

        $processes = $client->selectCollection('metrics', $counterTable);
        $monolith  = $client->selectCollection('metrics', $monolithTable);
        $bridge    = $client->selectCollection('metrics', $nodeTable);
        $connector = $client->selectCollection('metrics', $connectorTable);
        $rabbitmq  = $client->selectCollection('metrics', $rabbitTable);

        $doc = [
            'fields' => [
                'created'             => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
                'fpm_cpu_kernel_time' => 15,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $monolith->insertOne($doc);

        $doc = [
            'fields' => [
                'created'          => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
                'result_success'   => TRUE,
                'total_duration'   => 10,
                'waiting_duration' => 5,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $bridge->insertOne($doc);

        $doc = [
            'fields' => [
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
                'messages' => 3,
            ],
            'tags'   => [
                MongoMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                    $topology->getId(),
                    $node->getId(),
                    $node->getName(),
                ),
            ],
        ];
        $rabbitmq->insertOne($doc);

        $doc = [
            'fields' => [
                'created'                     => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
                'sent_request_total_duration' => 5,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $connector->insertOne($doc);

        $doc = [
            'fields' => [
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
                'duration' => 2,
                'result'   => TRUE,
            ],
            'tags'   => [
                MongoMetricsManager::NODE     => $node->getId(),
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
            ],
        ];
        $processes->insertOne($doc);
    }

    /**
     * @return DocumentManager
     */
    private function getMdm(): DocumentManager
    {
        return self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');
    }

    /**
     *
     */
    private function ensureCollections(): void
    {
        $dm = $this->getMdm();
        $dm->getSchemaManager()->dropCollections();
        $dm->getSchemaManager()->createCollections();
    }

    /**
     * @param string $bucketOffset
     * @param int    $count
     * @param int    $durationSum
     * @param string $nodeId
     * @param string $topologyId
     * @param int    $status200
     * @param int    $status400
     * @param int    $status500
     * @param int    $lastResponseCode
     *
     * @throws Exception
     */
    private function seedHourlyConnectorAggregate(
        string $bucketOffset,
        int $count,
        int $durationSum,
        string $nodeId = 'node-1',
        string $topologyId = 'topo-1',
        int $status200 = 0,
        int $status400 = 0,
        int $status500 = 0,
        int $lastResponseCode = 200,
    ): void
    {
        $bucket = new UTCDateTime(DateTimeUtils::getUtcDateTime($bucketOffset));

        $this->getMdm()->getClient()->selectCollection('metrics', 'hourly_connectors_aggregates')->insertOne(
            [
                'application_id'     => 'app-1',
                'bucket'             => $bucket,
                'count'              => $count,
                'duration_sum'       => $durationSum,
                'last_created'       => $bucket,
                'last_response_code' => $lastResponseCode,
                'node_id'            => $nodeId,
                'node_name'          => $nodeId,
                'status200'          => $status200,
                'status400'          => $status400,
                'status500'          => $status500,
                'topology_id'        => $topologyId,
            ],
        );
    }

    /**
     * @param string $bucketOffset
     * @param int    $count
     * @param int    $durationSum
     * @param string $nodeId
     * @param string $topologyId
     *
     * @throws Exception
     */
    private function seedHourlyPipesNodeAggregate(
        string $bucketOffset,
        int $count,
        int $durationSum,
        string $nodeId = 'node-1',
        string $topologyId = 'topo-1',
    ): void
    {
        $bucket = new UTCDateTime(DateTimeUtils::getUtcDateTime($bucketOffset));

        $this->getMdm()->getClient()->selectCollection('metrics', 'hourly_pipes_node_aggregates')->insertOne(
            [
                'bucket'       => $bucket,
                'count'        => $count,
                'duration_sum' => $durationSum,
                'node_id'      => $nodeId,
                'topology_id'  => $topologyId,
            ],
        );
    }

    /**
     * @param string $createdOffset
     * @param int    $duration
     * @param string $nodeId
     * @param string $topologyId
     *
     * @throws Exception
     */
    private function seedConnectorMetric(
        string $createdOffset,
        int $duration,
        string $nodeId,
        string $topologyId,
    ): void
    {
        /** @var string $connectorTable */
        $connectorTable = self::getContainer()->getParameter('mongodb.connector_table');

        $this->getMdm()->getClient()->selectCollection('metrics', $connectorTable)->insertOne(
            [
                'fields' => [
                    'created'                     => new UTCDateTime(DateTimeUtils::getUtcDateTime($createdOffset)),
                    'sent_request_total_duration' => $duration,
                ],
                'tags'   => [
                    'node_id'     => $nodeId,
                    'topology_id' => $topologyId,
                ],
            ],
        );
    }

    /**
     * @param string $createdOffset
     * @param int    $duration
     * @param string $nodeId
     * @param string $topologyId
     *
     * @throws Exception
     */
    private function seedPipesNodeMetric(
        string $createdOffset,
        int $duration,
        string $nodeId,
        string $topologyId,
    ): void
    {
        /** @var string $nodeTable */
        $nodeTable = self::getContainer()->getParameter('mongodb.node_table');

        $this->getMdm()->getClient()->selectCollection('metrics', $nodeTable)->insertOne(
            [
                'fields' => [
                    'created'         => new UTCDateTime(DateTimeUtils::getUtcDateTime($createdOffset)),
                    'worker_duration' => $duration,
                ],
                'tags'   => [
                    'node_id'     => $nodeId,
                    'topology_id' => $topologyId,
                ],
            ],
        );
    }

    /**
     * @param string $topologyId
     *
     * @return GridRequestDto
     * @throws Exception
     */
    private function topologyIdDto(string $topologyId): GridRequestDto
    {
        return new GridRequestDto(
            [
                'filter' => [
                    [
                        [
                            'column'   => 'topologyId',
                            'operator' => 'EQ',
                            'value'    => [$topologyId],
                        ],
                    ],
                ],
                'paging' => [
                    'itemsPerPage' => 9_999,
                    'page'         => 1,
                ],
                'sorter' => [
                    [
                        'column'    => 'duration',
                        'direction' => 'DESC',
                    ],
                ],
            ],
        );
    }

    /**
     * @param string $createdOffset
     *
     * @return GridRequestDto
     * @throws Exception
     */
    private function createdGteDto(string $createdOffset): GridRequestDto
    {
        return new GridRequestDto(
            [
                'filter' => [
                    [
                        [
                            'column'   => 'created',
                            'operator' => 'GTE',
                            'value'    => [
                                DateTimeUtils::getUtcDateTime($createdOffset)->format(DateTimeUtils::DATE_TIME_UTC),
                            ],
                        ],
                    ],
                ],
                'paging' => [
                    'itemsPerPage' => 100,
                    'page'         => 1,
                ],
            ],
        );
    }

    /**
     * @param string $createdFrom
     * @param string $createdTo
     *
     * @return GridRequestDto
     * @throws Exception
     */
    private function createdBetweenDto(string $createdFrom, string $createdTo): GridRequestDto
    {
        return new GridRequestDto(
            [
                'filter' => [
                    [
                        [
                            'column'   => 'created',
                            'operator' => 'BETWEEN',
                            'value'    => [
                                DateTimeUtils::getUtcDateTime($createdFrom)->format(DateTimeUtils::DATE_TIME_UTC),
                                DateTimeUtils::getUtcDateTime($createdTo)->format(DateTimeUtils::DATE_TIME_UTC),
                            ],
                        ],
                    ],
                ],
                'paging' => [
                    'itemsPerPage' => 100,
                    'page'         => 1,
                ],
            ],
        );
    }

}
