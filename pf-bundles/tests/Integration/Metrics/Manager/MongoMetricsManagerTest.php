<?php declare(strict_types=1);

namespace Tests\Integration\Metrics\Manager;

use Exception;
use Hanaboso\CommonsBundle\Database\Document\Node;
use Hanaboso\CommonsBundle\Database\Document\Topology;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\CommonsBundle\Utils\GeneratorUtils;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use MongoClient;
use Tests\KernelTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class MongoMetricsManagerTest
 *
 * @package Tests\Integration\Metrics\Manager
 */
final class MongoMetricsManagerTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @covers MongoMetricsManager::getNodeMetrics
     * @covers MongoMetricsManager::rabbitNodeMetrics
     * @covers MongoMetricsManager::connectorNodeMetrics
     * @covers MongoMetricsManager::bridgesNodeMetrics
     * @covers MongoMetricsManager::monolithNodeMetrics
     * @covers MongoMetricsManager::allowedTags
     * @covers MongoMetricsManager::addConditions
     *
     * @throws Exception
     */
    public function testGetNodeMetrics(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getNodeMetrics($node, $topo, [
            'from' => '-2 days',
            'to'   => '+2 days',
        ]);

        self::assertTrue(is_array($result));
        self::assertCount(6, $result);
        self::assertArrayHasKey(MongoMetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MongoMetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS, $result);

        self::assertEquals([
            MongoMetricsManager::QUEUE_DEPTH  => [
                'max' => '5',
                'avg' => '4.00',
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
        ], $result);
    }

    /**
     * @covers MongoMetricsManager::getNodeMetrics
     * @covers MongoMetricsManager::rabbitNodeMetrics
     * @covers MongoMetricsManager::connectorNodeMetrics
     * @covers MongoMetricsManager::bridgesNodeMetrics
     * @covers MongoMetricsManager::monolithNodeMetrics
     * @covers MongoMetricsManager::allowedTags
     * @covers MongoMetricsManager::addConditions
     *
     * @throws Exception
     */
    public function testGetNodeMetricsSingleDocument(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $this->setMinimalFakeData($topo, $node);

        $manager = $this->getManager();
        $result  = $manager->getNodeMetrics($node, $topo, [
            'from' => '-2 days',
            'to'   => '+2 days',
        ]);

        self::assertTrue(is_array($result));
        self::assertCount(6, $result);
        self::assertArrayHasKey(MongoMetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MongoMetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS, $result);

        self::assertEquals([
            MongoMetricsManager::QUEUE_DEPTH  => [
                'max' => '3',
                'avg' => '3.00',
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
        ], $result);
    }

    /**
     * @covers MongoMetricsManager::getNodeMetrics
     * @covers MongoMetricsManager::rabbitNodeMetrics
     * @covers MongoMetricsManager::connectorNodeMetrics
     * @covers MongoMetricsManager::bridgesNodeMetrics
     * @covers MongoMetricsManager::monolithNodeMetrics
     * @covers MongoMetricsManager::allowedTags
     * @covers MongoMetricsManager::addConditions
     *
     * @throws Exception
     */
    public function testGetNodeMetricsNoDocument(): void
    {
        $topo = $this->createTopo();
        $node = $this->createNode($topo);

        $manager = $this->getManager();
        $result  = $manager->getNodeMetrics($node, $topo, [
            'from' => '-2 days',
            'to'   => '+2 days',
        ]);

        self::assertTrue(is_array($result));
        self::assertCount(6, $result);
        self::assertArrayHasKey(MongoMetricsManager::QUEUE_DEPTH, $result);
        self::assertArrayHasKey(MongoMetricsManager::WAITING_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::CPU_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::REQUEST_TIME, $result);
        self::assertArrayHasKey(MongoMetricsManager::PROCESS, $result);

        self::assertEquals([
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
            MongoMetricsManager::REQUEST_TIME => [
                'max' => '0',
                'avg' => 'n/a',
                'min' => '0',
            ],
            MongoMetricsManager::PROCESS      => [
                'max'    => '0',
                'avg'    => '0.00',
                'min'    => '0',
                'total'  => '0',
                'errors' => '0',
            ],
        ], $result);
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
        $result  = $manager->getTopologyMetrics($topo, [
            'from' => '-2 days',
            'to'   => '+2 days',
        ]);

        self::assertTrue(is_array($result));
        self::assertCount(4, $result);
        self::assertEquals([
            MongoMetricsManager::PROCESS_TIME => [
                'min' => '2',
                'avg' => '2.00',
                'max' => '2',
            ],
            MongoMetricsManager::PROCESS      => [
                'total'  => '6',
                'errors' => '3',
            ],
        ], $result['topology']);
        self::assertArrayHasKey($node->getId(), $result);
        $result = $result[$node->getId()];

        self::assertTrue(is_array($result));
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
        $result  = $manager->getTopologyMetrics($topo, [
            'from' => '-2 days',
            'to'   => '+2 days',
        ]);

        self::assertTrue(is_array($result));
        self::assertCount(2, $result);
        self::assertEquals([
            MongoMetricsManager::PROCESS_TIME => [
                'min' => '2',
                'avg' => '2.00',
                'max' => '2',
            ],
            MongoMetricsManager::PROCESS      => [
                'total'  => '1',
                'errors' => '0',
            ],
        ], $result['topology']);
        self::assertArrayHasKey($node->getId(), $result);
    }

    /**
     * @throws Exception
     */
    public function testGetTopologyMetricsNoDocument(): void
    {
        $topo = $this->createTopo();

        $manager = $this->getManager();
        $result  = $manager->getTopologyMetrics($topo, [
            'from' => '-2 days',
            'to'   => '+2 days',
        ]);

        self::assertTrue(is_array($result));
        self::assertCount(1, $result);
        self::assertEquals([
            MongoMetricsManager::PROCESS_TIME => [
                'min' => '0',
                'avg' => '0.00',
                'max' => '0',
            ],
            MongoMetricsManager::PROCESS      => [
                'total'  => '0',
                'errors' => '0',
            ],
        ], $result['topology']);
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
        $result  = $manager->getTopologyRequestCountMetrics($topo, [
            'from' => '-10 day',
            'to'   => '+10 day',
        ]);

        self::assertTrue(is_array($result));
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
        $result  = $manager->getTopologyRequestCountMetrics($topo, [
            'from' => '-10 day',
            'to'   => '+10 day',
        ]);

        self::assertTrue(is_array($result));
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
        $result  = $manager->getTopologyRequestCountMetrics($topo, [
            'from' => '-10 day',
            'to'   => '+10 day',
        ]);

        self::assertTrue(is_array($result));
        self::assertCount(3, $result);
        self::assertCount(121, $result['requests']);
        self::assertEquals(0, array_sum($result['requests']));
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $client = $this->getClient();
        $client->selectDB('metrics')->drop();
        $this->ensureCollections();
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
     * @return MongoMetricsManager
     */
    private function getManager(): MongoMetricsManager
    {
        return self::$container->get('hbpf.metrics.manager.mongo_metrics');
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
        $client = $this->getClient();
        $this->setMinimalFakeData($topology, $node);

        $processes = $client->selectCollection('metrics', self::$container->getParameter('mongodb.counter_table'));
        $monolith  = $client->selectCollection('metrics', self::$container->getParameter('mongodb.monolith_table'));
        $bridge    = $client->selectCollection('metrics', self::$container->getParameter('mongodb.node_table'));
        $connector = $client->selectCollection('metrics', self::$container->getParameter('mongodb.connector_table'));
        $rabbitmq  = $client->selectCollection('metrics', self::$container->getParameter('mongodb.rabbit_table'));

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'bridge_job_result_success' => FALSE,
                'bridge_job_total_duration' => 4,
                'created'                   => DateTimeUtils::getUtcDateTime('-1 days')
                    ->getTimestamp(),
            ],
        ];
        $bridge->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::QUEUE => GeneratorUtils::generateQueueName($topology, $node),
            ],
            'fields' => [
                'messages' => 5,
                'created'  => DateTimeUtils::getUtcDateTime('-1 days')->getTimestamp(),
            ],
        ];
        $rabbitmq->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'sent_request_total_duration' => 15,
                'created'                     => DateTimeUtils::getUtcDateTime('-1 days')
                    ->getTimestamp(),
            ],
        ];
        $connector->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'fpm_cpu_kernel_time' => 5,
                'created'             => DateTimeUtils::getUtcDateTime('-1 days')->getTimestamp(),
            ],
        ];
        $monolith->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'bridge_job_result_success'   => TRUE,
                'bridge_job_waiting_duration' => 5,
                'bridge_job_total_duration'   => 10,
                'created'                     => DateTimeUtils::getUtcDateTime('+5 days')->getTimestamp(),
            ],
        ];
        $bridge->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'bridge_job_result_success' => FALSE,
                'bridge_job_total_duration' => 4,
                'created'                   => DateTimeUtils::getUtcDateTime('-51 days')
                    ->getTimestamp(),
            ],
        ];
        $bridge->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::QUEUE => GeneratorUtils::generateQueueName($topology, $node),
            ],
            'fields' => [
                'messages' => 3,
                'created'  => DateTimeUtils::getUtcDateTime('+5 days')->getTimestamp(),
            ],
        ];
        $rabbitmq->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::QUEUE => GeneratorUtils::generateQueueName($topology, $node),
            ],
            'fields' => [
                'messages' => 5,
                'created'  => DateTimeUtils::getUtcDateTime('-6 days')->getTimestamp(),
            ],
        ];
        $rabbitmq->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'sent_request_total_duration' => 5,
                'created'                     => DateTimeUtils::getUtcDateTime('+5 days')->getTimestamp(),
            ],
        ];
        $connector->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'sent_request_total_duration' => 15,
                'created'                     => DateTimeUtils::getUtcDateTime('-5 days')
                    ->getTimestamp(),
            ],
        ];
        $connector->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'fpm_cpu_kernel_time' => 15,
                'created'             => DateTimeUtils::getUtcDateTime('+5 days')->getTimestamp(),
            ],
        ];
        $monolith->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'fpm_cpu_kernel_time' => 5,
                'created'             => DateTimeUtils::getUtcDateTime('-5 days')->getTimestamp(),
            ],
        ];
        $monolith->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'counter_process_duration' => 2,
                'counter_process_result'   => FALSE,
                'created'                  => DateTimeUtils::getUtcDateTime($dateOffset)->getTimestamp(),
            ],
        ];
        $processes->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'counter_process_duration' => 2,
                'counter_process_result'   => FALSE,
                'created'                  => DateTimeUtils::getUtcDateTime('+55 days')->getTimestamp(),
            ],
        ];
        $processes->insert($doc);
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @throws Exception
     */
    private function setMinimalFakeData(Topology $topology, Node $node): void
    {
        $client = $this->getClient();

        $processes = $client->selectCollection('metrics', self::$container->getParameter('mongodb.counter_table'));
        $monolith  = $client->selectCollection('metrics', self::$container->getParameter('mongodb.monolith_table'));
        $bridge    = $client->selectCollection('metrics', self::$container->getParameter('mongodb.node_table'));
        $connector = $client->selectCollection('metrics', self::$container->getParameter('mongodb.connector_table'));
        $rabbitmq  = $client->selectCollection('metrics', self::$container->getParameter('mongodb.rabbit_table'));

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'fpm_cpu_kernel_time' => 15,
                'created'             => DateTimeUtils::getUtcDateTime()->getTimestamp(),
            ],
        ];
        $monolith->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'bridge_job_result_success'   => TRUE,
                'bridge_job_waiting_duration' => 5,
                'bridge_job_total_duration'   => 10,
                'created'                     => DateTimeUtils::getUtcDateTime()->getTimestamp(),
            ],
        ];
        $bridge->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::QUEUE => GeneratorUtils::generateQueueName($topology, $node),
            ],
            'fields' => [
                'messages' => 3,
                'created'  => DateTimeUtils::getUtcDateTime()->getTimestamp(),
            ],
        ];
        $rabbitmq->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'sent_request_total_duration' => 5,
                'created'                     => DateTimeUtils::getUtcDateTime()->getTimestamp(),
            ],
        ];
        $connector->insert($doc);

        $doc = [
            'tags'   => [
                MongoMetricsManager::TOPOLOGY => $topology->getId(),
                MongoMetricsManager::NODE     => $node->getId(),
            ],
            'fields' => [
                'counter_process_duration' => 2,
                'counter_process_result'   => TRUE,
                'created'                  => DateTimeUtils::getUtcDateTime()->getTimestamp(),
            ],
        ];
        $processes->insert($doc);
    }

    /**
     * @return MongoClient
     */
    private function getClient(): MongoClient
    {
        return self::$container->get('doctrine_mongodb.odm.metrics_document_manager')
            ->getConnection()
            ->getMongoClient();
    }

    /**
     *
     */
    private function ensureCollections(): void
    {
        $client = $this->getClient();
        $client->selectDB('metrics')->createCollection(self::$container->getParameter('mongodb.counter_table'));
        $client->selectDB('metrics')->createCollection(self::$container->getParameter('mongodb.monolith_table'));
        $client->selectDB('metrics')->createCollection(self::$container->getParameter('mongodb.node_table'));
        $client->selectDB('metrics')->createCollection(self::$container->getParameter('mongodb.connector_table'));
        $client->selectDB('metrics')->createCollection(self::$container->getParameter('mongodb.rabbit_table'));
    }

}
