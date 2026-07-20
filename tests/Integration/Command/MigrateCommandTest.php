<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\MigrationsMigrateCommand;
use Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;
use Hanaboso\PipesFramework\Metrics\Document\HourlyConnectorsAggregate;
use Hanaboso\PipesFramework\Metrics\Document\HourlyPipesNodeAggregate;
use Hanaboso\PipesFramework\Metrics\Document\MonolithMetrics;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Driver\Exception\CommandException;
use MongoDB\Model\IndexInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class MigrateCommandTest
 *
 * @package PipesFrameworkTests\Integration\Command
 */
#[CoversClass(MigrationsMigrateCommand::class)]
final class MigrateCommandTest extends DatabaseTestCaseAbstract
{

    private const string VERSION = 'create_multi_counter_status_and_duration';

    private const string HOURLY_VERSION = 'create_hourly_connectors_and_pipes_node_and_multi_counter_aggregates';

    private const array HOURLY_EXPIRE_INDEXES = [
        HourlyConnectorsAggregate::class => 'IK_hourly_connectors_aggregates_bucket',
        HourlyPipesNodeAggregate::class  => 'IK_hourly_pipes_node_aggregates_bucket',
    ];

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $collection = $this->dm->getDocumentCollection(TopologyProgress::class);
        $collection->insertMany(
            [
                $this->createDocument('completed', 1_005_000, 0, FALSE),
                $this->createDocument('failed', 1_008_000, 3, FALSE),
                $this->createDocument('running', NULL, 0, FALSE),
                $this->createDocument('terminated', 1_002_000, 1, TRUE),
            ],
        );

        /** @var DocumentManager $metricsDm */
        $metricsDm          = self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');
        $metricsCollections = [
            $metricsDm->getDocumentCollection(BridgesMetrics::class),
            $metricsDm->getDocumentCollection(ConnectorsMetrics::class),
            $metricsDm->getDocumentCollection(MonolithMetrics::class),
        ];

        foreach ($metricsCollections as $metricsCollection) {
            $this->createCorrelationIndex($metricsCollection);
        }

        $tester = $this->createCommandTester();
        $status = $tester->execute(['command' => 'migrations:migrate']);

        for ($attempt = 0; $status !== 0 && $attempt < 4; $attempt++) {
            usleep(250_000);

            $status = $tester->execute(['command' => 'migrations:migrate']);
        }

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('DONE', $tester->getDisplay());

        $this->dm->clear();

        self::assertSame(['COMPLETED', 5_000], $this->getStatusAndDuration('completed'));
        self::assertSame(['FAILED', 8_000], $this->getStatusAndDuration('failed'));
        self::assertSame(['TERMINATED', 2_000], $this->getStatusAndDuration('terminated'));

        /** @var TopologyProgress $running */
        $running = $this->dm->getRepository(TopologyProgress::class)->find('running');
        self::assertSame('RUNNING', $running->getStatus());
        self::assertGreaterThan(0, $running->getDuration());

        $migration = $this->dm->getDocumentDatabase(TopologyProgress::class)->selectCollection('migrations');
        self::assertContains(self::VERSION, $migration->distinct('_id'));

        $indexNames = [];
        foreach ($collection->listIndexes() as $index) {
            $indexNames[] = $index->getName();
        }

        self::assertContains('IK_multiCounter_status_duration', $indexNames);
        self::assertContains('IK_multiCounter_status_created', $indexNames);
        self::assertContains('IK_multiCounter_duration', $indexNames);
        self::assertContains('IK_multiCounter_status_topologyId_created', $indexNames);
        self::assertContains('IK_multiCounter_topologyId_duration', $indexNames);
        self::assertContains('IK_multiCounter_status_topologyId_duration', $indexNames);

        self::assertNotContains('IK_multiCounter_finished_created', $indexNames);
        self::assertNotContains('IK_multiCounter_nok_finished_created', $indexNames);
        self::assertNotContains('IK_multiCounter_topologyId_finished_created', $indexNames);
        self::assertNotContains('IK_multiCounter_topologyId_nok_finished_created', $indexNames);

        $userTaskIndexNames = [];

        foreach ($this->dm->getDocumentCollection(UserTask::class)->listIndexes() as $userTaskIndex) {
            $userTaskIndexNames[] = $userTaskIndex->getName();
        }

        self::assertContains('IK_userTask_correlationId', $userTaskIndexNames);

        foreach ($metricsCollections as $metricsCollection) {
            $metricsIndexNames = [];

            foreach ($metricsCollection->listIndexes() as $metricsIndex) {
                $metricsIndexNames[] = $metricsIndex->getName();
            }

            self::assertNotContains('tags_correlation_id_1', $metricsIndexNames);
        }

        $aggregates = $this
            ->dm
            ->getDocumentDatabase(TopologyProgress::class)
            ->selectCollection('hourly_multi_counter_aggregates');

        $aggregateIndexNames = [];

        foreach ($aggregates->listIndexes() as $aggregateIndex) {
            $aggregateIndexNames[] = $aggregateIndex->getName();
        }

        self::assertContains('UK_hourlyMultiCounterAggregate_topologyId_bucket', $aggregateIndexNames);
        self::assertContains('IK_hourlyMultiCounterAggregate_bucket', $aggregateIndexNames);

        $rows = $aggregates
            ->find([], ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']])
            ->toArray();

        self::assertCount(1, $rows);
        self::assertSame('topo', $rows[0]['topology_id']);
        self::assertSame(4, $rows[0]['count']);
        self::assertSame(2, $rows[0]['failed']);

        $tester = $this->createCommandTester();
        $tester->execute(['command' => 'migrations:migrate']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('SKIP', $tester->getDisplay());
        self::assertSame(7, $migration->countDocuments());
    }

    /**
     * @return void
     */
    public function testExecuteCreatesBucketExpireIndexes(): void
    {
        foreach ($this->migrateHourlyExpireIndexes(NULL) as $expireIndex) {
            self::assertSame(['bucket' => 1], $expireIndex->getKey());
            self::assertSame(2_592_000, $expireIndex['expireAfterSeconds']);
        }
    }

    /**
     * @return void
     */
    public function testExecuteKeepsConfiguredBucketExpireIndexes(): void
    {
        foreach ($this->migrateHourlyExpireIndexes(604_800) as $expireIndex) {
            self::assertSame(604_800, $expireIndex['expireAfterSeconds']);
        }
    }

    /**
     * @param int|null $expireAfterSeconds
     *
     * @return IndexInfo[]
     */
    private function migrateHourlyExpireIndexes(?int $expireAfterSeconds): array
    {
        /** @var DocumentManager $metricsDm */
        $metricsDm = self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');

        $display = '';

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            if ($attempt > 1) {
                usleep(250_000);
            }

            $this->dm->getDocumentDatabase(TopologyProgress::class)->selectCollection('migrations')->drop();

            foreach (self::HOURLY_EXPIRE_INDEXES as $documentClass => $expireIndexName) {
                $collection = $metricsDm->getDocumentCollection($documentClass);
                $collection->drop();

                if ($expireAfterSeconds !== NULL) {
                    $collection->createIndex(
                        ['bucket' => 1],
                        ['expireAfterSeconds' => $expireAfterSeconds, 'name' => $expireIndexName],
                    );
                }
            }

            $tester = $this->createCommandTester();
            $tester->execute(['command' => 'migrations:migrate']);
            $display = $tester->getDisplay();

            if ($tester->getStatusCode() !== 0) {
                continue;
            }

            self::assertStringContainsString(sprintf('DONE %s', self::HOURLY_VERSION), $display);

            $expireIndexes = [];

            foreach (self::HOURLY_EXPIRE_INDEXES as $documentClass => $expireIndexName) {
                $expireIndex = $this->findIndex($metricsDm->getDocumentCollection($documentClass), $expireIndexName);

                if ($expireIndex === NULL) {
                    continue;
                }

                if ($expireAfterSeconds !== NULL && $expireIndex['expireAfterSeconds'] !== $expireAfterSeconds) {
                    continue;
                }

                $expireIndexes[] = $expireIndex;
            }

            if (count($expireIndexes) === count(self::HOURLY_EXPIRE_INDEXES)) {
                return $expireIndexes;
            }
        }

        self::fail(sprintf('Migration never produced the expected bucket expire indexes: %s', $display));
    }

    /**
     * @param Collection $collection
     * @param string     $name
     *
     * @return IndexInfo|null
     */
    private function findIndex(Collection $collection, string $name): ?IndexInfo
    {
        foreach ($collection->listIndexes() as $index) {
            if ($index->getName() === $name) {
                return $index;
            }
        }

        return NULL;
    }

    /**
     * @param string $id
     *
     * @return array{string, int}
     */
    private function getStatusAndDuration(string $id): array
    {
        /** @var TopologyProgress $topologyProgress */
        $topologyProgress = $this->dm->getRepository(TopologyProgress::class)->find($id);

        return [$topologyProgress->getStatus(), $topologyProgress->getDuration()];
    }

    /**
     * @param string   $id
     * @param int|null $finishedMs
     * @param int      $nok
     * @param bool     $terminated
     *
     * @return mixed[]
     */
    private function createDocument(string $id, ?int $finishedMs, int $nok, bool $terminated): array
    {
        return [
            'created'        => new UTCDateTime(1_000_000),
            'finished'       => $finishedMs === NULL ? NULL : new UTCDateTime($finishedMs),
            'nok'            => $nok,
            'ok'             => 0,
            'processedCount' => 1,
            'source'         => 'auto',
            'terminated'     => $terminated,
            'topologyId'     => 'topo',
            'total'          => 1,
            'user'           => NULL,
            '_id'            => $id,
        ];
    }

    /**
     * @param Collection $collection
     *
     * @return void
     */
    private function createCorrelationIndex(Collection $collection): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            try {
                $collection->createIndex(['tags.correlation_id' => 1], ['name' => 'tags_correlation_id_1']);

                return;
            } catch (CommandException $e) {
                if ($attempt === 10) {
                    throw $e;
                }

                usleep(250_000);
            }
        }
    }

    /**
     * @return CommandTester
     */
    private function createCommandTester(): CommandTester
    {
        /** @var KernelInterface $kernel */
        $kernel = self::$kernel;

        return new CommandTester(new Application($kernel)->get('migrations:migrate'));
    }

}
