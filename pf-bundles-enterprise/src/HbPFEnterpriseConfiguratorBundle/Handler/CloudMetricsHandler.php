<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;
use Hanaboso\PipesFramework\Metrics\Document\RepeaterMetrics;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Throwable;

/**
 * Class CloudMetricsHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class CloudMetricsHandler
{

    public const string COLLECTION_RABBIT_METRICS = 'rabbitmq_metrics';

    public const string COLLECTION_RESOURCE_METRICS = 'resource_metrics';

    public const string COLLECTION_LOKI_METRICS = 'loki_retention_metrics';

    /**
     * CloudMetricsHandler constructor.
     *
     * @param DocumentManager         $dm
     * @param DocumentManager         $metricsDm
     * @param TopologyGeneratorBridge $generatorBridge
     * @param TopologyManager         $topologyManager
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly DocumentManager $metricsDm,
        private readonly TopologyGeneratorBridge $generatorBridge,
        private readonly TopologyManager $topologyManager,
    )
    {
    }

    /**
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getPublishedTopologiesCount(): array
    {
        /** @var TopologyRepository<Topology> $repo */
        $repo = $this->dm->getRepository(Topology::class);

        $enabled  = $repo->getCountByEnable(TRUE);
        $disabled = $repo->getCountByEnable(FALSE);

        return [
            'disabled' => $disabled,
            'enabled'  => $enabled,
            'total'    => $enabled + $disabled,
        ];
    }

    /**
     * @return mixed[]
     */
    public function getLimiterCount(): array
    {
        $headers = $this->topologyManager->getHeadersForTopologyRunRequest();

        return $this->generatorBridge->getLimiterSnapshot($headers);
    }

    /**
     * @param string $from
     * @param string $to
     * @param int    $buckets
     *
     * @return mixed[]
     */
    public function getLimiterHistory(string $from, string $to, int $buckets): array
    {
        $dateFrom = new UTCDateTime(new DateTime($from));
        $dateTo   = new UTCDateTime(new DateTime($to));

        $rangeMs = max(1, (int) (string) $dateTo - (int) (string) $dateFrom);
        $binSize = max(60_000, (int) ceil($rangeMs / max(1, $buckets)));

        return [
            'limiter'  => $this->aggregateHistory(LimiterMetrics::class, $dateFrom, $dateTo, $binSize),
            'repeater' => $this->aggregateHistory(RepeaterMetrics::class, $dateFrom, $dateTo, $binSize),
        ];
    }

    /**
     * CPU vCPU + Memory MB time-series from the K8s `resource_metrics`
     * collection (written by the metrics-collector when K8S_ENABLED=true).
     * Returns empty series when the collector is not running or the
     * collection does not exist yet.
     *
     * @param string $from    ISO-8601 inclusive range start
     * @param string $to      ISO-8601 exclusive range end
     * @param int    $buckets desired bucket count (binSize derived from range)
     *
     * @return mixed[]
     */
    public function getResourcesHistory(string $from, string $to, int $buckets): array
    {
        $dateFrom = new UTCDateTime(new DateTime($from));
        $dateTo   = new UTCDateTime(new DateTime($to));
        $binSize  = self::computeBinSize($dateFrom, $dateTo, $buckets);

        return [
            'binMs'  => $binSize,
            'cpu'    => $this->aggregateLatestNumeric(
                self::COLLECTION_RESOURCE_METRICS,
                'total_vcpu',
                'timestamp',
                $dateFrom,
                $dateTo,
                $binSize,
            ),
            'memory' => $this->aggregateLatestNumeric(
                self::COLLECTION_RESOURCE_METRICS,
                'total_memory_mb',
                'timestamp',
                $dateFrom,
                $dateTo,
                $binSize,
            ),
        ];
    }

    /**
     * Rabbit-only queue history (messages in flight + on-disk + in-RAM) from
     * `rabbitmq_metrics`. Limiter messages are intentionally NOT mixed in
     * so this matches what is actually sitting in RabbitMQ at each tick.
     *
     * @param string $from    ISO-8601 inclusive range start
     * @param string $to      ISO-8601 exclusive range end
     * @param int    $buckets desired bucket count (binSize derived from range)
     *
     * @return mixed[]
     */
    public function getQueueHistory(string $from, string $to, int $buckets): array
    {
        $dateFrom = new UTCDateTime(new DateTime($from));
        $dateTo   = new UTCDateTime(new DateTime($to));
        $binSize  = self::computeBinSize($dateFrom, $dateTo, $buckets);

        return [
            'binMs'    => $binSize,
            'diskMb'   => $this->aggregateLatestNumeric(
                self::COLLECTION_RABBIT_METRICS,
                'total_disk_mb',
                'timestamp',
                $dateFrom,
                $dateTo,
                $binSize,
            ),
            'messages' => $this->aggregateLatestNumeric(
                self::COLLECTION_RABBIT_METRICS,
                'total_messages',
                'timestamp',
                $dateFrom,
                $dateTo,
                $binSize,
            ),
            'ramMb'    => $this->aggregateLatestNumeric(
                self::COLLECTION_RABBIT_METRICS,
                'total_ram_mb',
                'timestamp',
                $dateFrom,
                $dateTo,
                $binSize,
            ),
        ];
    }

    /**
     * Latest log-retention snapshot from `loki_retention_metrics`. Returns
     * nullable values when the collection is missing (Loki disabled).
     *
     * @return mixed[]
     */
    public function getLogRetentionLatest(): array
    {
        $latest = $this->fetchLatestMetric(self::COLLECTION_LOKI_METRICS);

        return [
            'dailyDataSizeMb' => isset($latest['daily_data_size_mb']) ? round(
                (float) $latest['daily_data_size_mb'],
                2,
            ) : NULL,
            'oldestTimestamp' => isset($latest['oldest_timestamp']) && $latest['oldest_timestamp'] instanceof UTCDateTime
                ? $latest['oldest_timestamp']->toDateTime()->format(DATE_ATOM)
                : NULL,
            'retentionDays'   => isset($latest['retention_days']) ? (int) $latest['retention_days'] : NULL,
            'totalDataSizeMb' => isset($latest['total_data_size_mb']) ? round(
                (float) $latest['total_data_size_mb'],
                2,
            ) : NULL,
            'updatedAt'       => isset($latest['timestamp']) && $latest['timestamp'] instanceof UTCDateTime
                ? $latest['timestamp']->toDateTime()->format(DATE_ATOM)
                : NULL,
        ];
    }

    /**
     * Bucketed log-retention history (retention days + total data MB).
     *
     * @param string $from    ISO-8601 inclusive range start
     * @param string $to      ISO-8601 exclusive range end
     * @param int    $buckets desired bucket count (binSize derived from range)
     *
     * @return mixed[]
     */
    public function getLogRetentionHistory(string $from, string $to, int $buckets): array
    {
        $dateFrom = new UTCDateTime(new DateTime($from));
        $dateTo   = new UTCDateTime(new DateTime($to));
        $binSize  = self::computeBinSize($dateFrom, $dateTo, $buckets);

        return [
            'binMs'           => $binSize,
            'retentionDays'   => $this->aggregateLatestNumeric(
                self::COLLECTION_LOKI_METRICS,
                'retention_days',
                'timestamp',
                $dateFrom,
                $dateTo,
                $binSize,
            ),
            'totalDataSizeMb' => $this->aggregateLatestNumeric(
                self::COLLECTION_LOKI_METRICS,
                'total_data_size_mb',
                'timestamp',
                $dateFrom,
                $dateTo,
                $binSize,
            ),
        ];
    }

    /**
     * Per-bucket "latest sample" for a numeric scalar field on a `timestamp`-keyed
     * metrics collection. Mirrors {@see CloudLimitsHandler::aggregateLatestNumeric()}
     * but lives here as a private helper so the two handlers stay decoupled
     * (CloudLimitsHandler stays focused on limit-bound aggregates).
     *
     * @param string      $collection       metrics collection name
     * @param string      $valueField       BSON field path to read
     * @param string      $timeField        BSON field path with sample timestamp
     * @param UTCDateTime $from             inclusive range start
     * @param UTCDateTime $to               exclusive range end
     * @param int         $binSize          bucket size in milliseconds
     * @param bool        $sumAcrossSamples sum same-tick samples before $last
     *
     * @return mixed[]
     */
    private function aggregateLatestNumeric(
        string $collection,
        string $valueField,
        string $timeField,
        UTCDateTime $from,
        UTCDateTime $to,
        int $binSize,
        bool $sumAcrossSamples = FALSE,
    ): array
    {
        try {
            $coll = $this->metricsCollection($collection);

            $pipeline = CloudLimitsHandler::buildLatestNumericPipeline(
                $valueField,
                $timeField,
                $from,
                $to,
                $binSize,
                $sumAcrossSamples,
            );

            $out = [];
            foreach ($coll->aggregate(
                $pipeline,
                ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']],
            ) as $row) {
                $out[] = (array) $row;
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param string $collection metrics collection name
     *
     * @return mixed[]
     */
    private function fetchLatestMetric(string $collection): array
    {
        try {
            $cursor = $this->metricsCollection($collection)->find(
                [],
                [
                    'limit'   => 1,
                    'sort'    => ['timestamp' => -1],
                    'typeMap' => ['array' => 'array', 'document' => 'array', 'root' => 'array'],
                ],
            );
            foreach ($cursor as $doc) {
                return (array) $doc;
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }

    /**
     * @param string $collection metrics collection name
     *
     * @return Collection
     */
    private function metricsCollection(string $collection): Collection
    {
        return $this->metricsDm->getDocumentDatabase(LimiterMetrics::class)->selectCollection($collection);
    }

    /**
     * @param class-string $documentClass
     * @param UTCDateTime  $from
     * @param UTCDateTime  $to
     * @param int          $binSize
     *
     * @return mixed[]
     */
    private function aggregateHistory(string $documentClass, UTCDateTime $from, UTCDateTime $to, int $binSize): array
    {
        try {
            $builder = $this->metricsDm->createAggregationBuilder($documentClass);

            $builder
                ->match()
                ->field('fields.created')->gte($from)
                ->field('fields.created')->lt($to);

            $builder
                ->group()
                ->field('_id')
                ->dateTrunc('$fields.created', 'minute')
                ->field('countAtMinute')
                ->sum('$fields.messages');

            $fromMs = (int) (string) $from;

            $builder
                ->group()
                ->field('_id')
                ->toDate(
                    $builder->expr()->add(
                        $builder->expr()->toLong($from),
                        $builder->expr()->multiply(
                            $builder->expr()->floor(
                                $builder->expr()->divide(
                                    $builder->expr()->subtract(
                                        $builder->expr()->toLong('$_id'),
                                        $fromMs,
                                    ),
                                    $binSize,
                                ),
                            ),
                            $binSize,
                        ),
                    ),
                )
                ->field('count')
                ->max('$countAtMinute');

            $builder
                ->sort(['_id' => 'asc'])
                ->project()
                ->field('_id')
                ->expression(FALSE)
                ->field('created')
                ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$_id')
                ->field('count')
                ->round('$count');

            return $builder->execute()->toArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param UTCDateTime $from    inclusive range start
     * @param UTCDateTime $to      exclusive range end
     * @param int         $buckets desired bucket count
     *
     * @return int
     */
    private static function computeBinSize(UTCDateTime $from, UTCDateTime $to, int $buckets): int
    {
        $rangeMs = max(1, (int) (string) $to - (int) (string) $from);

        return max(60_000, (int) ceil($rangeMs / max(1, $buckets)));
    }

}
