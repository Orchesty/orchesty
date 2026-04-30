<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudMetricsHandler;
use LogicException;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\CursorInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class CloudMetricsHandlerTest
 *
 * Coverage for the new resources / queue / log-retention helpers exposed
 * by the cloud admin metrics endpoints. Mocks the metrics DocumentManager
 * to assert response shape without a live MongoDB.
 *
 * @package PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler
 */
#[CoversClass(CloudMetricsHandler::class)]
#[AllowMockObjectsWithoutExpectations]
final class CloudMetricsHandlerTest extends TestCase
{

    /**
     * @return void
     */
    public function testGetResourcesHistoryReturnsCpuAndMemorySeriesShape(): void
    {
        $handler = $this->makeHandler([]);

        $history = $handler->getResourcesHistory('2026-04-30T00:00:00Z', '2026-04-30T01:00:00Z', 60);

        self::assertArrayHasKey('cpu', $history);
        self::assertArrayHasKey('memory', $history);
        self::assertArrayHasKey('binMs', $history);
        self::assertSame([], $history['cpu']);
        self::assertSame([], $history['memory']);
        self::assertGreaterThanOrEqual(60_000, $history['binMs']);
    }

    /**
     * @return void
     */
    public function testGetQueueHistoryReturnsRabbitOnlyTriple(): void
    {
        $handler = $this->makeHandler([]);

        $history = $handler->getQueueHistory('2026-04-30T00:00:00Z', '2026-04-30T01:00:00Z', 60);

        self::assertArrayHasKey('messages', $history);
        self::assertArrayHasKey('diskMb', $history);
        self::assertArrayHasKey('ramMb', $history);
        self::assertArrayHasKey('binMs', $history);
        self::assertSame([], $history['messages']);
    }

    /**
     * @return void
     */
    public function testGetLogRetentionLatestReturnsAllNullsWhenCollectionEmpty(): void
    {
        $handler = $this->makeHandler([]);

        $latest = $handler->getLogRetentionLatest();

        self::assertNull($latest['retentionDays']);
        self::assertNull($latest['totalDataSizeMb']);
        self::assertNull($latest['dailyDataSizeMb']);
        self::assertNull($latest['oldestTimestamp']);
        self::assertNull($latest['updatedAt']);
    }

    /**
     * @return void
     */
    public function testGetLogRetentionLatestSerializesUtcDateTimes(): void
    {
        $oldest = new UTCDateTime(strtotime('2026-04-01T00:00:00Z') * 1_000);
        $stamp  = new UTCDateTime(strtotime('2026-04-30T00:30:00Z') * 1_000);

        $handler = $this->makeHandler([
            CloudMetricsHandler::COLLECTION_LOKI_METRICS => [
                'daily_data_size_mb' => 42.4,
                'oldest_timestamp'   => $oldest,
                'retention_days'     => 29,
                'timestamp'          => $stamp,
                'total_data_size_mb' => 1_234.567,
            ],
        ]);

        $latest = $handler->getLogRetentionLatest();

        self::assertSame(29, $latest['retentionDays']);
        self::assertSame(1_234.57, $latest['totalDataSizeMb']);
        self::assertSame(42.4, $latest['dailyDataSizeMb']);
        self::assertSame('2026-04-01T00:00:00+00:00', $latest['oldestTimestamp']);
        self::assertSame('2026-04-30T00:30:00+00:00', $latest['updatedAt']);
    }

    /**
     * @return void
     */
    public function testGetLogRetentionHistoryReturnsRetentionAndTotalSeries(): void
    {
        $handler = $this->makeHandler([]);

        $history = $handler->getLogRetentionHistory('2026-04-30T00:00:00Z', '2026-04-30T01:00:00Z', 60);

        self::assertArrayHasKey('retentionDays', $history);
        self::assertArrayHasKey('totalDataSizeMb', $history);
        self::assertArrayHasKey('binMs', $history);
        self::assertSame([], $history['retentionDays']);
    }

    /**
     * Builds a CloudMetricsHandler whose metrics DocumentManager returns
     * the supplied per-collection latest doc (or an empty cursor when not
     * specified). aggregate() always returns empty so history methods
     * exercise their error-path returning [].
     *
     * @param array<string, array<string, mixed>> $latestPerCollection
     */
    private function makeHandler(array $latestPerCollection): CloudMetricsHandler
    {
        $collections = [];
        foreach ([
            CloudMetricsHandler::COLLECTION_RABBIT_METRICS,
            CloudMetricsHandler::COLLECTION_RESOURCE_METRICS,
            CloudMetricsHandler::COLLECTION_LOKI_METRICS,
        ] as $name) {
            $coll   = $this->createMock(Collection::class);
            $latest = $latestPerCollection[$name] ?? NULL;
            $coll->method('find')->willReturn(self::makeCursor($latest === NULL ? [] : [$latest]));
            $coll->method('aggregate')->willReturn(self::makeCursor([]));
            $collections[$name] = $coll;
        }

        $metricsDb = $this->createMock(Database::class);
        $metricsDb->method('selectCollection')->willReturnCallback(
            static fn(string $name): Collection => $collections[$name]
                ?? throw new LogicException(sprintf('Unexpected metrics collection: %s', $name)),
        );

        $metricsDm = $this->createMock(DocumentManager::class);
        $metricsDm->method('getDocumentDatabase')->willReturnCallback(
            static fn(string $class): Database => $class === LimiterMetrics::class ? $metricsDb : $metricsDb,
        );

        return new CloudMetricsHandler(
            $this->createMock(DocumentManager::class),
            $metricsDm,
            $this->createMock(TopologyGeneratorBridge::class),
            $this->createMock(TopologyManager::class),
        );
    }

    /**
     * @param array<int, mixed> $items
     */
    private static function makeCursor(array $items): CursorInterface
    {
        return new FakeMongoCursor($items);
    }

}
