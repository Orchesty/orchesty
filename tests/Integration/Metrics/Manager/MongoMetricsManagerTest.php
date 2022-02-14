<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\NodeGeneratorUtils;
use LogicException;
use MongoDB\BSON\UTCDateTime;
use Monolog\Logger;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use PipesFrameworkTests\DataProvider;

/**
 * Class MongoMetricsManagerTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Manager
 */
final class MongoMetricsManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::rabbitNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::connectorNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::bridgesNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::monolithNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::allowedTags
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::addConditions
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::generateOutput
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::setLogger
     *
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
                MongoMetricsManager::QUEUE_DEPTH  => [
                    'max' => '5',
                    'avg' => '0.00',
                ],
                MongoMetricsManager::WAITING_TIME => [
                    'max' => '5',
                    'avg' => '2.50',
                    'min' => '0',
                ],
                MongoMetricsManager::PROCESS_TIME => [
                    'max' => '10',
                    'avg' => '7.00',
                    'min' => '4',
                ],
                MongoMetricsManager::CPU_TIME     => [
                    'max' => '15',
                    'avg' => '10.00',
                    'min' => '5',
                ],
                MongoMetricsManager::REQUEST_TIME => [
                    'max' => '15',
                    'avg' => '10.00',
                    'min' => '5',
                ],
                MongoMetricsManager::PROCESS      => [
                    'max'    => '0',
                    'avg'    => '0.00',
                    'min'    => '0',
                    'total'  => '2',
                    'errors' => '1',
                ],
            ],
            $result,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::rabbitNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::connectorNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::bridgesNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::monolithNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::allowedTags
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::addConditions
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::generateOutput
     *
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
                MongoMetricsManager::QUEUE_DEPTH  => [
                    'max' => '3',
                    'avg' => '0.00',
                ],
                MongoMetricsManager::WAITING_TIME => [
                    'max' => '5',
                    'avg' => '5.00',
                    'min' => '5',
                ],
                MongoMetricsManager::PROCESS_TIME => [
                    'max' => '10',
                    'avg' => '10.00',
                    'min' => '10',
                ],
                MongoMetricsManager::CPU_TIME     => [
                    'max' => '15',
                    'avg' => '15.00',
                    'min' => '15',
                ],
                MongoMetricsManager::REQUEST_TIME => [
                    'max' => '5',
                    'avg' => '5.00',
                    'min' => '5',
                ],
                MongoMetricsManager::PROCESS      => [
                    'max'    => '0',
                    'avg'    => '0.00',
                    'min'    => '0',
                    'total'  => '1',
                    'errors' => '0',
                ],
            ],
            $result,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::rabbitNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::connectorNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::bridgesNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::monolithNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::allowedTags
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::addConditions
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::generateOutput
     *
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
                MongoMetricsManager::QUEUE_DEPTH  => [
                    'max' => '0',
                    'avg' => '0.00',
                ],
                MongoMetricsManager::WAITING_TIME => [
                    'max' => '0',
                    'avg' => '0.00',
                    'min' => '0',
                ],
                MongoMetricsManager::PROCESS_TIME => [
                    'max' => '0',
                    'avg' => '0.00',
                    'min' => '0',
                ],
                MongoMetricsManager::CPU_TIME     => [
                    'max' => '0',
                    'avg' => '0.00',
                    'min' => '0',
                ],
                MongoMetricsManager::PROCESS      => [
                    'max'    => '0',
                    'avg'    => '0.00',
                    'min'    => '0',
                    'total'  => '0',
                    'errors' => '0',
                ],
            ],
            $result,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::counterProcessMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::generateOutput
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::rabbitNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::connectorNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::monolithNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::bridgesNodeMetrics
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
                MongoMetricsManager::PROCESS_TIME => [
                    'min' => '2',
                    'avg' => '2.00',
                    'max' => '2',
                ],
                MongoMetricsManager::PROCESS      => [
                    'total'  => '6',
                    'errors' => '3',
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::counterProcessMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::generateOutput
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::rabbitNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::connectorNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::monolithNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::bridgesNodeMetrics
     *
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
                MongoMetricsManager::PROCESS_TIME => [
                    'min' => '2',
                    'avg' => '2.00',
                    'max' => '2',
                ],
                MongoMetricsManager::PROCESS      => [
                    'total'  => '1',
                    'errors' => '0',
                ],
            ],
            $result['topology'],
        );
        self::assertArrayHasKey($node->getId(), $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::counterProcessMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::generateOutput
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::rabbitNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::connectorNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::monolithNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::bridgesNodeMetrics
     *
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
                MongoMetricsManager::PROCESS_TIME => [
                    'min' => '0',
                    'avg' => '0.00',
                    'max' => '0',
                ],
                MongoMetricsManager::PROCESS      => [
                    'total'  => '0',
                    'errors' => '0',
                ],
            ],
            $result['topology'],
        );

        self::expectException(LogicException::class);
        $manager->getTopologyMetrics($topo, []);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyRequestCountMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::requestsCountAggregation
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::addConditions
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::allowedTags
     *
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyRequestCountMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::requestsCountAggregation
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::addConditions
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::allowedTags
     *
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyRequestCountMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::requestsCountAggregation
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::addConditions
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::allowedTags
     *
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getApplicationMetrics
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologiesProcessTimeMetrics
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getConsumerMetrics
     * @throws DateTimeException
     * @throws Exception
     */
    public function testConsumerMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);
        $this->setFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getConsumerMetrics([]);
        self::assertEquals(
            [
                [
                    'queue'     => 'pipes.limiter',
                    'consumers' => 0,
                    'created'   => $result[0]['created'],
                ],
            ],
            $result,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getUserMetrics
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
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureCollections();
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
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'result_success' => FALSE,
                'total_duration' => 4,
                'created'        => new UTCDateTime(DateTimeUtils::getUtcDateTime('-1 days')),
            ],
        ];
        $bridge->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                    $topology->getId(),
                    $node->getId(),
                    $node->getName(),
                ),
            ],
            'fields' => [
                'messages' => 5,
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime('-1 days')),
            ],
        ];
        $rabbitmq->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY    => $topology->getId(),
                MongoMetricsManager::NODE        => $node->getId(),
                MongoMetricsManager::APPLICATION => 'nutshell',
                MongoMetricsManager::USER        => 'user2',
                MongoMetricsManager::CORRELATION => '456-789',
            ],
            'fields' => [
                'sent_request_total_duration' => 15,
                'created'                     => new UTCDateTime(DateTimeUtils::getUtcDateTime('-1 days')),
            ],
        ];
        $connector->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'fpm_cpu_kernel_time' => 5,
                'created'             => new UTCDateTime(DateTimeUtils::getUtcDateTime('-1 days')),
            ],
        ];
        $monolith->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'result_success'   => TRUE,
                'waiting_duration' => 5,
                'total_duration'   => 10,
                'created'          => new UTCDateTime(DateTimeUtils::getUtcDateTime('+5 days')),
            ],
        ];
        $bridge->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'result_success' => FALSE,
                'total_duration' => 4,
                'created'        => new UTCDateTime(DateTimeUtils::getUtcDateTime('-51 days')),
            ],
        ];
        $bridge->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                    $topology->getId(),
                    $node->getId(),
                    $node->getName(),
                ),
            ],
            'fields' => [
                'messages' => 3,
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime('+5 days')),
            ],
        ];
        $rabbitmq->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                    $topology->getId(),
                    $node->getId(),
                    $node->getName(),
                ),
            ],
            'fields' => [
                'messages' => 5,
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime('-6 days')),
            ],
        ];
        $rabbitmq->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY    => $topology->getId(),
                MongoMetricsManager::NODE        => $node->getId(),
                MongoMetricsManager::APPLICATION => 'nutshell',
                MongoMetricsManager::USER        => 'user1',
                MongoMetricsManager::CORRELATION => '456-789',
            ],
            'fields' => [
                'sent_request_total_duration' => 5,
                'created'                     => new UTCDateTime(DateTimeUtils::getUtcDateTime('+5 days')),
            ],
        ];
        $connector->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY    => $topology->getId(),
                MongoMetricsManager::NODE        => $node->getId(),
                MongoMetricsManager::APPLICATION => 'nutshell',
                MongoMetricsManager::USER        => 'user1',
                MongoMetricsManager::CORRELATION => '123-456',
            ],
            'fields' => [
                'sent_request_total_duration' => 15,
                'created'                     => new UTCDateTime(DateTimeUtils::getUtcDateTime('-5 days')),
            ],
        ];
        $connector->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'fpm_cpu_kernel_time' => 15,
                'created'             => new UTCDateTime(DateTimeUtils::getUtcDateTime('+5 days')),
            ],
        ];
        $monolith->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'fpm_cpu_kernel_time' => 5,
                'created'             => new UTCDateTime(DateTimeUtils::getUtcDateTime('-5 days')),
            ],
        ];
        $monolith->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'duration' => 2,
                'result'   => FALSE,
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime($dateOffset)),
            ],
        ];
        $processes->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'duration' => 2,
                'result'   => FALSE,
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime('+55 days')),
            ],
        ];
        $processes->insertOne($doc);

        $doc = [
            'tags'   => [
                'queue'     => 'pipes.limiter',
                'consumers' => 0,
            ],
            'fields' => [
                'created' => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
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
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'fpm_cpu_kernel_time' => 15,
                'created'             => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
            ],
        ];
        $monolith->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'result_success'   => TRUE,
                'waiting_duration' => 5,
                'total_duration'   => 10,
                'created'          => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
            ],
        ];
        $bridge->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::QUEUE => NodeGeneratorUtils::generateQueueName(
                    $topology->getId(),
                    $node->getId(),
                    $node->getName(),
                ),
            ],
            'fields' => [
                'messages' => 3,
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
            ],
        ];
        $rabbitmq->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'sent_request_total_duration' => 5,
                'created'                     => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
            ],
        ];
        $connector->insertOne($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'duration' => 2,
                'result'   => TRUE,
                'created'  => new UTCDateTime(DateTimeUtils::getUtcDateTime()),
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

}
