<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Command;

use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command\MigrationsMigrateCommand;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use MongoDB\BSON\UTCDateTime;
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

        $tester = $this->createCommandTester();
        $tester->execute(['command' => 'migrations:migrate']);

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
        self::assertSame(6, $migration->countDocuments());
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
     * @return CommandTester
     */
    private function createCommandTester(): CommandTester
    {
        /** @var KernelInterface $kernel */
        $kernel = self::$kernel;

        return new CommandTester(new Application($kernel)->get('migrations:migrate'));
    }

}
