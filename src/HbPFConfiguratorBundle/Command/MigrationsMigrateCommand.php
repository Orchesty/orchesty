<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Closure;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\HourlyMultiCounterAggregate;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;
use Hanaboso\PipesFramework\Metrics\Document\HourlyConnectorsAggregate;
use Hanaboso\PipesFramework\Metrics\Document\HourlyPipesNodeAggregate;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class MigrationsMigrateCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 */
class MigrationsMigrateCommand extends Command
{

    private const string NAME       = 'migrations:migrate';
    private const string COLLECTION = 'migrations';

    /**
     * MigrationsMigrateCommand constructor.
     *
     * @param DocumentManager $dm
     * @param DocumentManager $metricsDm
     */
    public function __construct(protected readonly DocumentManager $dm, protected readonly DocumentManager $metricsDm)
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(self::NAME);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input;

        $collection = $this
            ->dm
            ->getDocumentDatabase(TopologyProgress::class)
            ->selectCollection(self::COLLECTION);

        $appliedMigrations = $collection->distinct('_id');

        foreach ($this->getMigrations() as $version => $migration) {
            if (in_array($version, $appliedMigrations, TRUE)) {
                $output->writeln(sprintf(' SKIP %s', $version));

                continue;
            }

            try {
                $migration();
                $collection->insertOne(['_id' => $version, 'executedAt' => new UTCDateTime()]);
                $output->writeln(sprintf(' DONE %s', $version));
            } catch (Throwable $e) {
                $output->writeln(sprintf(' FAIL %s (%s)', $version, $e->getMessage()));

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, Closure(): void>
     */
    protected function getMigrations(): array
    {
        // phpcs:ignore SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
        return [
            'create_multi_counter_status_and_duration' => function (): void {
                $this->createMultiCounterStatusAndDuration();
            },
            'update_multi_counter_indexes' => function (): void {
                $this->updateMultiCounterIndexes();
            },
            'update_multi_counter_indexes2' => function (): void {
                $this->updateMultiCounterIndexes2();
            },
            'update_connector_indexes' => function (): void {
                $this->updateConnectorIndexes();
            },
            'update_user_task_indexes' => function (): void {
                $this->updateUserTaskIndexes();
            },
            'create_hourly_connectors_and_pipes_node_and_multi_counter_aggregates' => function (): void {
                $this->createHourlyConnectorsAndPipesNodeAndMultiCounterAggregates();
            },
        ];
    }

    /**
     * @return void
     */
    private function createMultiCounterStatusAndDuration(): void
    {
        $collection = $this->dm->getDocumentCollection(TopologyProgress::class);

        $collection->updateMany(
            ['$or' => [['status' => ['$exists' => FALSE]], ['duration' => ['$exists' => FALSE]]]],
            [
                [
                    '$set' => [
                        'duration' => ['$subtract' => [['$ifNull' => ['$finished', '$$NOW']], '$created']],
                        'status' => [
                            '$cond' => [
                                ['$eq' => ['$terminated', TRUE]],
                                'TERMINATED',
                                [
                                    '$cond' => [
                                        ['$eq' => ['$finished', NULL]],
                                        'RUNNING',
                                        ['$cond' => [['$gt' => ['$nok', 0]], 'FAILED', 'COMPLETED']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $collection->createIndex(['status' => 1, 'duration' => -1], ['name' => 'IK_multiCounter_status_duration']);
        $collection->createIndex(['status' => 1, 'created' => -1], ['name' => 'IK_multiCounter_status_created']);
        $collection->createIndex(['duration' => -1], ['name' => 'IK_multiCounter_duration']);
    }

    /**
     * @return void
     */
    private function updateMultiCounterIndexes(): void
    {
        $collection = $this->dm->getDocumentCollection(TopologyProgress::class);

        $collection->createIndex(
            ['status' => 1, 'topologyId' => 1, 'created' => -1],
            ['name' => 'IK_multiCounter_status_topologyId_created'],
        );

        $collection->createIndex(
            ['topologyId' => 1, 'duration' => -1],
            ['name' => 'IK_multiCounter_topologyId_duration'],
        );

        $collection->createIndex(
            ['status' => 1, 'topologyId' => 1, 'duration' => -1],
            ['name' => 'IK_multiCounter_status_topologyId_duration'],
        );

        $indexes = [];

        foreach ($collection->listIndexes() as $index) {
            $indexes[] = $index->getName();
        }

        $oldIndexes = [
            'IK_multiCounter_finished_created',
            'IK_multiCounter_nok_finished_created',
            'IK_multiCounter_topologyId_finished_created',
            'IK_multiCounter_topologyId_nok_finished_created',
        ];

        foreach ($oldIndexes as $index) {
            if (in_array($index, $indexes, TRUE)) {
                $collection->dropIndex($index);
            }
        }
    }

    /**
     * @return void
     */
    private function updateMultiCounterIndexes2(): void
    {
        $collection = $this->dm->getDocumentCollection(TopologyProgress::class);

        $collection->createIndex(
            ['created' => 1, 'topologyId' => 1, 'status' => 1],
            ['name' => 'IK_multiCounter_created_topologyId_status'],
        );

        $indexes = [];

        foreach ($collection->listIndexes() as $index) {
            $indexes[] = $index->getName();
        }

        if (in_array('IK_multiCounter_created_topologyId_nok_finished', $indexes, TRUE)) {
            $collection->dropIndex('IK_multiCounter_created_topologyId_nok_finished');
        }
    }

    /**
     * @return void
     */
    private function updateConnectorIndexes(): void
    {
        $collection = $this->metricsDm->getDocumentCollection(ConnectorsMetrics::class);

        $collection->createIndex(
            ['tags.correlation_id' => 1, 'fields.created' => 1],
            ['name' => 'IK_connector_tagsCorrelationId_fieldsCreated'],
        );

        $indexes = [];

        foreach ($collection->listIndexes() as $index) {
            $indexes[] = $index->getName();
        }

        $oldIndexes = [
            'IK_connector_tagsCorrelationId_tagsNodeId_fieldsCreated',
            'IK_connector_tagsTopologyId_fieldsCreated',
            'IK_connector_fieldsCreated_tagsNodeId_fieldsResponseCode_tagsApplicationId',
            'IK_connector_fieldsResponseCode_tagsNodeId_fieldsCreated',
            'IK_connector_tagsApplicationId_tagsNodeId_fieldsCreated',
            'IK_connector_fieldsResponseCode_tagsApplicationId_tagsNodeId_fieldsCreated',
            'IK_connector_tagsApplicationId_tagsTopologyId_tagsNodeId_fieldsCreated',
            'IK_connector_fieldsResponseCode_tagsApplicationId_tagsTopologyId_tagsNodeId_fieldsCreated',
        ];

        foreach ($oldIndexes as $index) {
            if (in_array($index, $indexes, TRUE)) {
                $collection->dropIndex($index);
            }
        }
    }

    /**
     * @return void
     */
    private function updateUserTaskIndexes(): void
    {
        $collection = $this->dm->getDocumentCollection(UserTask::class);

        $collection->createIndex(['correlationId' => 1], ['name' => 'IK_userTask_correlationId']);
    }

    /**
     * @return void
     */
    private function createHourlyConnectorsAndPipesNodeAndMultiCounterAggregates(): void
    {
        $this->metricsDm->getDocumentCollection(HourlyConnectorsAggregate::class)->createIndex(
            ['node_id' => 1, 'bucket' => 1],
            ['name' => 'UK_hourlyConnectorAggregate_nodeId_bucket', 'unique' => TRUE],
        );

        $this->metricsDm->getDocumentCollection(ConnectorsMetrics::class)->aggregate(
            [
                [
                    '$group' => [
                        'application_id'     => ['$first' => '$tags.application_id'],
                        'count'              => ['$sum' => 1],
                        'duration_sum'       => ['$sum' => '$fields.sent_request_total_duration'],
                        'last_created'       => ['$max' => '$fields.created'],
                        'last_response_code' => [
                            '$top' => ['output' => '$fields.response_code', 'sortBy' => ['fields.created' => -1]],
                        ],
                        'node_name'          => ['$first' => '$tags.node_name'],
                        'status200'          => [
                            '$sum' => [
                                '$cond' => [
                                    [
                                        '$and' => [
                                            ['$gte' => ['$fields.response_code', 200]],
                                            ['$lte' => ['$fields.response_code', 399]],
                                        ],
                                    ],
                                    1,
                                    0,
                                ],
                            ],
                        ],
                        'status400'          => [
                            '$sum' => [
                                '$cond' => [
                                    [
                                        '$and' => [
                                            ['$gte' => ['$fields.response_code', 400]],
                                            ['$lte' => ['$fields.response_code', 499]],
                                        ],
                                    ],
                                    1,
                                    0,
                                ],
                            ],
                        ],
                        'status500'          => [
                            '$sum' => [
                                '$cond' => [
                                    [
                                        '$and' => [
                                            ['$gte' => ['$fields.response_code', 500]],
                                            ['$lte' => ['$fields.response_code', 599]],
                                        ],
                                    ],
                                    1,
                                    0,
                                ],
                            ],
                        ],
                        'topology_id'        => ['$first' => '$tags.topology_id'],
                        '_id' => [
                            'bucket'  => [
                                '$dateTrunc' => ['binSize' => 15, 'date' => '$fields.created', 'unit' => 'minute'],
                            ],
                            'node_id' => '$tags.node_id',
                        ],
                    ],
                ],
                ['$set' => ['bucket' => '$_id.bucket', 'node_id' => '$_id.node_id']],
                ['$unset' => '_id'],
                [
                    '$merge' => [
                        'into'           => 'hourly_connectors_aggregates',
                        'on'             => ['node_id', 'bucket'],
                        'whenMatched'    => 'replace',
                        'whenNotMatched' => 'insert',
                    ],
                ],
            ],
            ['allowDiskUse' => TRUE],
        );

        $this->metricsDm->getDocumentCollection(HourlyPipesNodeAggregate::class)->createIndex(
            ['node_id' => 1, 'bucket' => 1],
            ['name' => 'UK_hourlyPipesNodeAggregate_nodeId_bucket', 'unique' => TRUE],
        );

        $this->metricsDm->getDocumentCollection(BridgesMetrics::class)->aggregate(
            [
                [
                    '$group' => [
                        'count'        => ['$sum' => 1],
                        'duration_sum' => ['$sum' => '$fields.worker_duration'],
                        'topology_id'  => ['$first' => '$tags.topology_id'],
                        '_id'          => [
                            'bucket'  => [
                                '$dateTrunc' => ['binSize' => 15, 'date' => '$fields.created', 'unit' => 'minute'],
                            ],
                            'node_id' => '$tags.node_id',
                        ],
                    ],
                ],
                ['$set' => ['bucket' => '$_id.bucket', 'node_id' => '$_id.node_id']],
                ['$unset' => '_id'],
                [
                    '$merge' => [
                        'into'           => 'hourly_pipes_node_aggregates',
                        'on'             => ['node_id', 'bucket'],
                        'whenMatched'    => 'replace',
                        'whenNotMatched' => 'insert',
                    ],
                ],
            ],
            ['allowDiskUse' => TRUE],
        );

        $this->dm->getDocumentCollection(HourlyMultiCounterAggregate::class)->createIndex(
            ['topology_id' => 1, 'bucket' => 1],
            ['name' => 'UK_hourlyMultiCounterAggregate_topologyId_bucket', 'unique' => TRUE],
        );

        $this->dm->getDocumentCollection(HourlyMultiCounterAggregate::class)->createIndex(
            ['bucket' => 1],
            ['expireAfterSeconds' => 2_628_000, 'name' => 'IK_hourlyMultiCounterAggregate_bucket'],
        );

        $this->dm->getDocumentCollection(TopologyProgress::class)->aggregate(
            [
                [
                    '$group' => [
                        'count'        => ['$sum' => 1],
                        'failed'       => [
                            '$sum' => [
                                '$cond' => [
                                    ['$in' => ['$status', ['FAILED', 'TERMINATED']]],
                                    1,
                                    0,
                                ],
                            ],
                        ],
                        'last_created' => ['$max' => '$created'],
                        'last_status'  => [
                            '$top' => ['output' => '$status', 'sortBy' => ['created' => -1]],
                        ],
                        '_id'          => [
                            'bucket'      => [
                                '$dateTrunc' => ['binSize' => 15, 'date' => '$created', 'unit' => 'minute'],
                            ],
                            'topology_id' => '$topologyId',
                        ],
                    ],
                ],
                ['$set' => ['bucket' => '$_id.bucket', 'topology_id' => '$_id.topology_id']],
                ['$unset' => '_id'],
                [
                    '$merge' => [
                        'into'           => 'hourly_multi_counter_aggregates',
                        'on'             => ['topology_id', 'bucket'],
                        'whenMatched'    => 'replace',
                        'whenNotMatched' => 'insert',
                    ],
                ],
            ],
            ['allowDiskUse' => TRUE],
        );

        $targets = [
            [ConnectorsMetrics::class, 'IK_connector_tagsTopologyId_tagsNodeId_fieldsCreated'],
            [BridgesMetrics::class, 'IK_pipesNode_tagsTopologyId_tagsNodeId_fieldsCreated'],
        ];

        $collections = iterator_to_array(
            $this->metricsDm->getDocumentDatabase(ConnectorsMetrics::class)->listCollectionNames(),
        );

        foreach ($targets as [$documentClass, $name]) {
            $collection = $this->metricsDm->getDocumentCollection($documentClass);
            $indexes    = [];

            if (in_array($collection->getCollectionName(), $collections, TRUE)) {
                foreach ($collection->listIndexes() as $index) {
                    $indexes[] = $index->getName();
                }
            }

            if (in_array($name, $indexes, TRUE)) {
                $collection->dropIndex($name);
            }

            $collection->createIndex(
                ['tags.topology_id' => 1, 'tags.node_id' => 1, 'fields.created' => -1],
                ['name' => $name],
            );
        }
    }

}
