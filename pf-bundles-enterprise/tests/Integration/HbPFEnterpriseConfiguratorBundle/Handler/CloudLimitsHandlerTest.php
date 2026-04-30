<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Limiter;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudLimitsHandler;
use LogicException;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\CursorInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class CloudLimitsHandlerTest
 *
 * @package PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler
 */
#[CoversClass(CloudLimitsHandler::class)]
#[AllowMockObjectsWithoutExpectations]
final class CloudLimitsHandlerTest extends TestCase
{

    /**
     * @param int|float  $current
     * @param int|float  $limit
     * @param float|null $expected
     */
    #[DataProvider('percentProvider')]
    public function testPercentEdgeCases(int|float $current, int|float $limit, ?float $expected): void
    {
        self::assertSame($expected, CloudLimitsHandler::percent($current, $limit));
    }

    /**
     * @param int|float $current
     * @param int|float $limit
     * @param string    $expected
     */
    #[DataProvider('bandProvider')]
    public function testBandThresholds(int|float $current, int|float $limit, string $expected): void
    {
        self::assertSame($expected, CloudLimitsHandler::band($current, $limit));
    }

    /**
     * @return void
     */
    public function testBandsToReportSkipsNoneAndConvertsStorageLimit(): void
    {
        $usage = [
            'band'    => [
                'messages' => CloudLimitsHandler::BAND_CRITICAL,
                'storage'  => CloudLimitsHandler::BAND_NONE,
            ],
            'limits'  => ['messages' => 1_000, 'storageGb' => 10, 'topologySlots' => 0],
            'percent' => ['messages' => 95.0, 'storage' => 39.1, 'slots' => NULL],
            'usage'   => ['messages' => 950, 'storageMb' => 4_000, 'topologySlots' => 1],
        ];

        $report = CloudLimitsHandler::bandsToReport($usage);
        self::assertCount(1, $report);
        self::assertSame('messages', $report[0]['resource']);
        self::assertSame(CloudLimitsHandler::BAND_CRITICAL, $report[0]['band']);
        self::assertSame(950, $report[0]['current']);
        self::assertSame(1_000, $report[0]['limit']);
    }

    /**
     * @return void
     */
    public function testBandsToReportConvertsStorageLimitToMb(): void
    {
        $usage = [
            'band'    => [
                'messages' => CloudLimitsHandler::BAND_NONE,
                'storage'  => CloudLimitsHandler::BAND_EXCEEDED,
            ],
            'limits'  => ['messages' => 0, 'storageGb' => 10, 'topologySlots' => 0],
            'percent' => ['messages' => NULL, 'storage' => 102.5, 'slots' => NULL],
            'usage'   => ['messages' => 0, 'storageMb' => 10_500, 'topologySlots' => 0],
        ];

        $report = CloudLimitsHandler::bandsToReport($usage);
        self::assertCount(1, $report);
        self::assertSame('storage', $report[0]['resource']);
        self::assertSame(CloudLimitsHandler::BAND_EXCEEDED, $report[0]['band']);
        self::assertSame(10_500, $report[0]['current']);
        self::assertSame(10 * 1_024.0, $report[0]['limit']);
    }

    /**
     * Default mode (used for rabbit/mongo/loki collectors that emit one global
     * row per tick) keeps the original single-stage `$last` per bucket.
     */
    public function testBuildLatestNumericPipelineDefaultIsSingleLastPerBucket(): void
    {
        $from = new UTCDateTime(new DateTime('2026-04-30T00:00:00Z'));
        $to   = new UTCDateTime(new DateTime('2026-04-30T01:00:00Z'));

        $pipeline = CloudLimitsHandler::buildLatestNumericPipeline(
            'total_messages',
            'timestamp',
            $from,
            $to,
            60_000,
            FALSE,
        );

        $stages = array_map(static fn(array $stage): string => (string) array_key_first($stage), $pipeline);
        self::assertSame(
            ['$match', '$project', '$sort', '$group', '$sort', '$project'],
            $stages,
        );

        $group = $pipeline[3]['$group'];
        self::assertSame('$bucket', $group['_id']);
        self::assertSame(['$last' => '$value'], $group['value']);
    }

    /**
     * "Sum across samples" mode (used for the limiter time-series, which has
     * N rows per tick — one per node) must collapse same-tick rows via `$sum`
     * before applying `$last` per bucket. Without this, the chart would show
     * a single node's count instead of the cluster-wide total.
     */
    public function testBuildLatestNumericPipelineSumsAcrossSamplesPerTick(): void
    {
        $from = new UTCDateTime(new DateTime('2026-04-30T00:00:00Z'));
        $to   = new UTCDateTime(new DateTime('2026-04-30T01:00:00Z'));

        $pipeline = CloudLimitsHandler::buildLatestNumericPipeline(
            'fields.messages',
            'fields.created',
            $from,
            $to,
            60_000,
            TRUE,
        );

        $stages = array_map(static fn(array $stage): string => (string) array_key_first($stage), $pipeline);
        self::assertSame(
            ['$match', '$project', '$group', '$sort', '$group', '$sort', '$project'],
            $stages,
            'Sum mode must insert an extra per-tick $group before the per-bucket $last.',
        );

        // First group: sum across nodes per (bucket, minute).
        $perTick = $pipeline[2]['$group'];
        self::assertSame(['$sum' => '$value'], $perTick['value']);
        self::assertArrayHasKey('bucket', $perTick['_id']);
        self::assertArrayHasKey('tick', $perTick['_id']);
        self::assertSame(
            ['$dateTrunc' => ['date' => '$time', 'unit' => 'minute']],
            $perTick['_id']['tick'],
        );

        // Second group: latest per-tick total per bucket.
        $perBucket = $pipeline[4]['$group'];
        self::assertSame('$_id.bucket', $perBucket['_id']);
        self::assertSame(['$last' => '$value'], $perBucket['value']);

        // Sort between the two groups must order by tick time, not bucket id.
        self::assertSame(['_id.tick' => 1], $pipeline[3]['$sort']);
    }

    /**
     * @return void
     */
    public function testBuildLatestNumericPipelineMatchesRequestedTimeWindow(): void
    {
        $from = new UTCDateTime(new DateTime('2026-04-30T00:00:00Z'));
        $to   = new UTCDateTime(new DateTime('2026-04-30T01:00:00Z'));

        $pipeline = CloudLimitsHandler::buildLatestNumericPipeline(
            'total_messages',
            'timestamp',
            $from,
            $to,
            60_000,
            FALSE,
        );

        self::assertSame(
            ['timestamp' => ['$gte' => $from, '$lt' => $to]],
            $pipeline[0]['$match'],
        );
    }

    /**
     * `getUsageSplit()` exposes per-source rabbit/limiter/mongo/loki numbers
     * AND keeps the merged totals consistent with `getUsage()`.
     */
    public function testGetUsageSplitProducesSplitShapeAndKeepsMergedTotals(): void
    {
        $rabbit = ['total_messages' => 200, 'total_disk_mb' => 12.5];
        $mongo  = ['storage_size_mb' => 80.5];
        $loki   = ['total_data_size_mb' => 7.0];

        $handler = $this->makeHandlerWithMetrics(
            limiterCount: 50,
            metricsByCollection: [
                CloudLimitsHandler::COLLECTION_DB_STORAGE_METRICS => $mongo,
                CloudLimitsHandler::COLLECTION_LOKI_METRICS       => $loki,
                CloudLimitsHandler::COLLECTION_RABBIT_METRICS    => $rabbit,
            ],
            publishedSlots: 7,
            limitMessages: 1_000,
            limitStorageGb: 1,
        );

        $split = $handler->getUsageSplit();

        self::assertSame(200, $split['split']['messages']['rabbit']);
        self::assertSame(50, $split['split']['messages']['limiter']);
        self::assertSame(80.5, $split['split']['storage']['mongoMb']);
        self::assertSame(12.5, $split['split']['storage']['rabbitMb']);
        self::assertSame(7.0, $split['split']['storage']['lokiMb']);

        // Merged totals must equal sum of split parts.
        self::assertSame(250, $split['usage']['messages']);
        self::assertSame(round(80.5 + 12.5 + 7.0, 2), $split['usage']['storageMb']);
        self::assertSame(7, $split['usage']['topologySlots']);

        self::assertSame(1_000, $split['limits']['messages']);
        self::assertSame(1, $split['limits']['storageGb']);
        self::assertSame(25.0, $split['percent']['messages']);
        // 100MB used / 1024MB limit
        self::assertSame(round(100.0 / 1_024.0 * 100, 1), $split['percent']['storage']);
    }

    /**
     * @return void
     */
    public function testGetUsageSplitHandlesEmptyCollections(): void
    {
        $handler = $this->makeHandlerWithMetrics(
            limiterCount: 0,
            metricsByCollection: [],
            publishedSlots: 0,
            limitMessages: 0,
            limitStorageGb: 0,
        );

        $split = $handler->getUsageSplit();

        self::assertSame(0, $split['split']['messages']['rabbit']);
        self::assertSame(0, $split['split']['messages']['limiter']);
        self::assertSame(0.0, $split['split']['storage']['mongoMb']);
        self::assertSame(0, $split['usage']['messages']);
        self::assertSame(0.0, $split['usage']['storageMb']);
        self::assertNull($split['percent']['messages']);
        self::assertNull($split['percent']['storage']);
    }

    /**
     * @return void
     */
    public function testGetHistorySplitReturnsTwoCategoriesAndBin(): void
    {
        $handler = $this->makeHandlerWithMetrics(
            limiterCount: 0,
            metricsByCollection: [],
            publishedSlots: 0,
            limitMessages: 0,
            limitStorageGb: 0,
        );

        $history = $handler->getHistorySplit('2026-04-30T00:00:00Z', '2026-04-30T01:00:00Z', 60);

        self::assertArrayHasKey('messages', $history);
        self::assertArrayHasKey('storage', $history);
        self::assertArrayHasKey('binMs', $history);
        self::assertArrayHasKey('rabbit', $history['messages']);
        self::assertArrayHasKey('limiter', $history['messages']);
        self::assertArrayHasKey('mongo', $history['storage']);
        self::assertArrayHasKey('rabbit', $history['storage']);
        self::assertArrayHasKey('loki', $history['storage']);

        // Empty collections -> empty series.
        self::assertSame([], $history['messages']['rabbit']);
        self::assertSame([], $history['messages']['limiter']);
        self::assertSame([], $history['storage']['loki']);
        self::assertGreaterThanOrEqual(60_000, $history['binMs']);
    }

    /**
     * @return mixed[][]
     */
    public static function percentProvider(): array
    {
        return [
            'half'                           => [50, 100, 50.0],
            'negative limit treated as null' => [10, -1, NULL],
            'overflow keeps reporting'       => [200, 100, 200.0],
            'rounded to one decimal'         => [333, 1_000, 33.3],
            'unlimited returns null'         => [10, 0, NULL],
        ];
    }

    /**
     * @return mixed[][]
     */
    public static function bandProvider(): array
    {
        return [
            'critical at 90'    => [90, 100, CloudLimitsHandler::BAND_CRITICAL],
            'critical near 100' => [99, 100, CloudLimitsHandler::BAND_CRITICAL],
            'exceeded above'    => [250, 100, CloudLimitsHandler::BAND_EXCEEDED],
            'exceeded at 100'   => [100, 100, CloudLimitsHandler::BAND_EXCEEDED],
            'just under warn'   => [79, 100, CloudLimitsHandler::BAND_NONE],
            'low usage'         => [10, 100, CloudLimitsHandler::BAND_NONE],
            'no limit -> none'  => [10, 0, CloudLimitsHandler::BAND_NONE],
            'warning at 80'     => [80, 100, CloudLimitsHandler::BAND_WARNING],
        ];
    }

    /**
     * @param int                                 $limiterCount
     * @param array<string, array<string, mixed>> $metricsByCollection
     * @param int                                 $publishedSlots
     * @param int                                 $limitMessages
     * @param int                                 $limitStorageGb
     *
     * @return CloudLimitsHandler
     */
    private function makeHandlerWithMetrics(
        int $limiterCount,
        array $metricsByCollection,
        int $publishedSlots,
        int $limitMessages,
        int $limitStorageGb,
    ): CloudLimitsHandler
    {
        $topologyRepo = $this->createMock(TopologyRepository::class);
        $topologyRepo->method('getPublishedCount')->willReturn($publishedSlots);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static fn(string $class) => $class === Topology::class ? $topologyRepo : NULL,
        );

        // Limiter doc-count + dummy DB used by snapshot persist (unused in tests).
        $limiterCollection = $this->createMock(Collection::class);
        $limiterCollection->method('countDocuments')->willReturn($limiterCount);
        $limiterDb = $this->createMock(Database::class);
        $limiterDb->method('selectCollection')->willReturn($limiterCollection);

        // Per-metric collection mocks. Calls to find() return latest-doc array
        // (single element); aggregate() returns empty (used by history split).
        $metricsCollections = [];
        foreach ([
            CloudLimitsHandler::COLLECTION_RABBIT_METRICS,
            CloudLimitsHandler::COLLECTION_DB_STORAGE_METRICS,
            CloudLimitsHandler::COLLECTION_LOKI_METRICS,
            'limiter',
        ] as $name) {
            $coll   = $this->createMock(Collection::class);
            $latest = $metricsByCollection[$name] ?? NULL;
            $coll->method('find')->willReturn(self::makeCursor($latest === NULL ? [] : [$latest]));
            $coll->method('aggregate')->willReturn(self::makeCursor([]));
            $metricsCollections[$name] = $coll;
        }

        $metricsDb = $this->createMock(Database::class);
        $metricsDb->method('selectCollection')->willReturnCallback(
            static fn(string $name): Collection => $metricsCollections[$name]
                ?? throw new LogicException(sprintf('Unexpected metrics collection: %s', $name)),
        );

        $dm->method('getDocumentDatabase')->willReturnCallback(
            static fn(string $class): Database => $class === Limiter::class ? $limiterDb : $limiterDb,
        );

        $metricsDm = $this->createMock(DocumentManager::class);
        $metricsDm->method('getDocumentDatabase')->willReturnCallback(
            static fn(string $class): Database => $class === LimiterMetrics::class ? $metricsDb : $metricsDb,
        );

        return new CloudLimitsHandler($dm, $metricsDm, $limitMessages, $limitStorageGb, 0);
    }

    /**
     * Build a CursorInterface-compatible iterator backed by a plain array.
     * MongoDB Collection methods declare CursorInterface as return type, so
     * a stock ArrayIterator is rejected by PHPUnit's strict return-type
     * checks. {@see FakeMongoCursor} keeps the boilerplate out of the test
     * body.
     *
     * @param array<int, mixed> $items
     */
    private static function makeCursor(array $items): CursorInterface
    {
        return new FakeMongoCursor($items);
    }

}
