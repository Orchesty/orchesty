<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\NodeGeneratorUtils;
use LogicException;
use MongoDB\BSON\UTCDateTime;
use Monolog\Logger;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use PipesFrameworkTests\DataProvider;
use Throwable;

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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::counterProcessMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::generateOutput
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::counterProcessMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::generateOutput
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
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getTopologyProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::getNodeMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::parseDateRange
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::counterProcessMetrics
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager::generateOutput
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

        try {
            $this->ensureCollections();
        } catch (Throwable $t) {
            $t;
            //
        }
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

}
