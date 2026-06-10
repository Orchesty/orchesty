<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Closure;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
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
     */
    public function __construct(protected readonly DocumentManager $dm)
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
        return [
            'create_multi_counter_status_and_duration' => function (): void {
                $this->createMultiCounterStatusAndDuration();
            },
            'update_multi_counter_indexes' => function (): void {
                $this->updateMultiCounterIndexes();
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

}
