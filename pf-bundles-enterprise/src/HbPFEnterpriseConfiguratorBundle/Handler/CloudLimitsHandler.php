<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Document\Limiter;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Throwable;

/**
 * Class CloudLimitsHandler
 *
 * Computes and serves cloud plan-limit usage (messages-in-flight, storage, topology slots),
 * persists the latest snapshot for cheap polling, and renders historical series for the
 * Resources dashboard tab. Usage figures intentionally mirror what the Go bridge enforces
 * in `bridge/pkg/bridge/limits.go` so the UI cannot disagree with the actual drop trigger.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class CloudLimitsHandler
{

    public const string SNAPSHOT_COLLECTION = 'cloud_limit_usage';

    public const string SNAPSHOT_KEY = 'singleton';

    public const string COLLECTION_RABBIT_METRICS = 'rabbitmq_metrics';

    public const string COLLECTION_DB_STORAGE_METRICS = 'db_storage_metrics';

    public const string COLLECTION_LOKI_METRICS = 'loki_retention_metrics';

    public const string BAND_NONE = 'none';

    public const string BAND_WARNING = 'warning';

    public const string BAND_CRITICAL = 'critical';

    public const string BAND_EXCEEDED = 'exceeded';

    private const int THRESHOLD_WARNING_PCT = 80;

    private const int THRESHOLD_CRITICAL_PCT = 90;

    /**
     * CloudLimitsHandler constructor.
     *
     * @param DocumentManager $dm                 main DocumentManager (Topology, Limiter, snapshot)
     * @param DocumentManager $metricsDm          metrics DocumentManager (rabbit/storage/loki/limiter history)
     * @param int             $limitMessages
     * @param int             $limitStorageGb
     * @param int             $limitTopologySlots
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly DocumentManager $metricsDm,
        private readonly int $limitMessages = 0,
        private readonly int $limitStorageGb = 0,
        private readonly int $limitTopologySlots = 0,
    )
    {
    }

    /**
     * Recompute usage from live data sources and return the canonical snapshot
     * shape that is also persisted for the polling endpoint to read.
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function computeUsage(): array
    {
        /** @var TopologyRepository<Topology> $topologyRepo */
        $topologyRepo = $this->dm->getRepository(Topology::class);

        $rabbit  = $this->fetchLatestMetric(self::COLLECTION_RABBIT_METRICS);
        $storage = $this->fetchLatestMetric(self::COLLECTION_DB_STORAGE_METRICS);
        $loki    = $this->fetchLatestMetric(self::COLLECTION_LOKI_METRICS);

        // Use raw countDocuments() to avoid Mongo's deprecated count() metadata
        // fast-path, which can drift from reality (e.g. after unclean shutdown
        // or orphan operations) and report a non-zero count for an empty
        // collection. countDocuments() always reflects actual data.
        $limiterCount = $this->dm
            ->getDocumentDatabase(Limiter::class)
            ->selectCollection('limiter')
            ->countDocuments();

        $messagesUsed  = $limiterCount + (int) ($rabbit['total_messages'] ?? 0);
        $storageMbUsed = (float) ($storage['storage_size_mb'] ?? 0)
            + (float) ($rabbit['total_disk_mb'] ?? 0)
            + (float) ($loki['total_data_size_mb'] ?? 0);

        // Slot = published topology row (any version, enabled or disabled).
        // Disabling a topology does NOT free the slot - only decommission /
        // unpublish / delete does. Same source as the Resources page bridge
        // grid so the two surfaces always agree.
        $slotsUsed      = $topologyRepo->getPublishedCount();
        $storageMbLimit = $this->limitStorageGb > 0 ? $this->limitStorageGb * 1_024.0 : 0.0;

        return [
            'band'      => [
                'messages' => self::band($messagesUsed, $this->limitMessages),
                'storage'  => self::band($storageMbUsed, $storageMbLimit),
            ],
            'limits'    => [
                'messages'      => $this->limitMessages,
                'storageGb'     => $this->limitStorageGb,
                'topologySlots' => $this->limitTopologySlots,
            ],
            'percent'   => [
                'messages' => self::percent($messagesUsed, $this->limitMessages),
                'slots'    => self::percent($slotsUsed, $this->limitTopologySlots),
                'storage'  => self::percent($storageMbUsed, $storageMbLimit),
            ],
            'updatedAt' => (new DateTime())->format(DATE_ATOM),
            'usage'     => [
                'messages'      => $messagesUsed,
                'storageMb'     => round($storageMbUsed, 2),
                'topologySlots' => $slotsUsed,
            ],
        ];
    }

    /**
     * Returns the latest persisted snapshot or, if missing, recomputes it on the
     * fly. Used by the public polling endpoint so it stays O(1).
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getUsage(): array
    {
        $coll = $this->dm->getDocumentDatabase(Topology::class)->selectCollection(self::SNAPSHOT_COLLECTION);
        $doc  = $coll->findOne(['_id' => self::SNAPSHOT_KEY]);

        if ($doc === NULL) {
            return $this->computeUsage();
        }

        $arr     = (array) $doc;
        $payload = $arr['payload'] ?? NULL;
        if (!is_array($payload) && !is_object($payload)) {
            return $this->computeUsage();
        }
        $payload = (array) $payload;

        if (isset($arr['updatedAt']) && $arr['updatedAt'] instanceof UTCDateTime) {
            $payload['updatedAt'] = $arr['updatedAt']->toDateTime()->format(DATE_ATOM);
        }

        // Recursively cast BSON Documents to plain arrays so JSON serialization
        // doesn't leak BSON type wrappers into the API response.
        return $this->bsonToArray($payload);
    }

    /**
     * Persist a snapshot computed by {@see computeUsage()} to the singleton
     * `cloud_limit_usage` doc. Called by the {@see CloudLimitsTickCommand}.
     *
     * @param mixed[] $usage
     */
    public function persistSnapshot(array $usage): void
    {
        $coll = $this->dm->getDocumentDatabase(Topology::class)->selectCollection(self::SNAPSHOT_COLLECTION);
        $coll->updateOne(
            ['_id' => self::SNAPSHOT_KEY],
            ['$set' => [
                'payload'   => $usage,
                'updatedAt' => new UTCDateTime(),
            ]],
            ['upsert' => TRUE],
        );
    }

    /**
     * Bucketed history for the Resources tab. Returns parallel series for
     * messages-in-flight (RabbitMQ total_messages + Limiter doc count) and
     * storage MB (Mongo + RabbitMQ disk + Loki retention).
     *
     * @param string $from
     * @param string $to
     * @param int    $buckets
     *
     * @return mixed[]
     */
    public function getHistory(string $from, string $to, int $buckets): array
    {
        $dateFrom = new UTCDateTime(new DateTime($from));
        $dateTo   = new UTCDateTime(new DateTime($to));

        $rangeMs = max(1, (int) (string) $dateTo - (int) (string) $dateFrom);
        $binSize = max(60_000, (int) ceil($rangeMs / max(1, $buckets)));

        return [
            'binMs'    => $binSize,
            'messages' => $this->aggregateMessagesHistory($dateFrom, $dateTo, $binSize),
            'storage'  => $this->aggregateStorageHistory($dateFrom, $dateTo, $binSize),
        ];
    }

    /**
     * Latest snapshot with **per-source breakdown** (no merging). Mirrors
     * {@see computeUsage()} but exposes each component separately so admin
     * UIs can show "what is in Rabbit vs what is in Limiter" and "what
     * storage is held by Mongo vs Rabbit vs Loki". Limits and bands are
     * computed against the merged totals (consistent with
     * {@see getUsage()}).
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getUsageSplit(): array
    {
        /** @var TopologyRepository<Topology> $topologyRepo */
        $topologyRepo = $this->dm->getRepository(Topology::class);

        $rabbit  = $this->fetchLatestMetric(self::COLLECTION_RABBIT_METRICS);
        $storage = $this->fetchLatestMetric(self::COLLECTION_DB_STORAGE_METRICS);
        $loki    = $this->fetchLatestMetric(self::COLLECTION_LOKI_METRICS);

        $limiterCount = $this->dm
            ->getDocumentDatabase(Limiter::class)
            ->selectCollection('limiter')
            ->countDocuments([]);

        $rabbitMessages = (int) ($rabbit['total_messages'] ?? 0);
        $rabbitDiskMb   = (float) ($rabbit['total_disk_mb'] ?? 0);
        $mongoStorageMb = (float) ($storage['storage_size_mb'] ?? 0);
        $lokiStorageMb  = (float) ($loki['total_data_size_mb'] ?? 0);

        $messagesUsed   = $limiterCount + $rabbitMessages;
        $storageMbUsed  = $mongoStorageMb + $rabbitDiskMb + $lokiStorageMb;
        $slotsUsed      = $topologyRepo->getPublishedCount();
        $storageMbLimit = $this->limitStorageGb > 0 ? $this->limitStorageGb * 1_024.0 : 0.0;

        return [
            'band'      => [
                'messages' => self::band($messagesUsed, $this->limitMessages),
                'storage'  => self::band($storageMbUsed, $storageMbLimit),
            ],
            'limits'    => [
                'messages'      => $this->limitMessages,
                'storageGb'     => $this->limitStorageGb,
                'topologySlots' => $this->limitTopologySlots,
            ],
            'percent'   => [
                'messages' => self::percent($messagesUsed, $this->limitMessages),
                'slots'    => self::percent($slotsUsed, $this->limitTopologySlots),
                'storage'  => self::percent($storageMbUsed, $storageMbLimit),
            ],
            'split'     => [
                'messages' => [
                    'limiter' => $limiterCount,
                    'rabbit'  => $rabbitMessages,
                ],
                'storage'  => [
                    'lokiMb'   => round($lokiStorageMb, 2),
                    'mongoMb'  => round($mongoStorageMb, 2),
                    'rabbitMb' => round($rabbitDiskMb, 2),
                ],
            ],
            'updatedAt' => (new DateTime())->format(DATE_ATOM),
            'usage'     => [
                'messages'      => $messagesUsed,
                'storageMb'     => round($storageMbUsed, 2),
                'topologySlots' => $slotsUsed,
            ],
        ];
    }

    /**
     * Bucketed history with per-source breakdown. Returns parallel series for
     * messages (rabbit + limiter) and storage (mongo + rabbit + loki). Each
     * sub-series uses the same bucket alignment so the frontend can stack
     * them or render side-by-side.
     *
     * @param string $from
     * @param string $to
     * @param int    $buckets
     *
     * @return mixed[]
     */
    public function getHistorySplit(string $from, string $to, int $buckets): array
    {
        $dateFrom = new UTCDateTime(new DateTime($from));
        $dateTo   = new UTCDateTime(new DateTime($to));

        $rangeMs = max(1, (int) (string) $dateTo - (int) (string) $dateFrom);
        $binSize = max(60_000, (int) ceil($rangeMs / max(1, $buckets)));

        return [
            'binMs'    => $binSize,
            'messages' => [
                'limiter' => $this->aggregateLatestNumeric(
                    'limiter',
                    'fields.messages',
                    'fields.created',
                    $dateFrom,
                    $dateTo,
                    $binSize,
                    TRUE,
                ),
                'rabbit'  => $this->aggregateLatestNumeric(
                    self::COLLECTION_RABBIT_METRICS,
                    'total_messages',
                    'timestamp',
                    $dateFrom,
                    $dateTo,
                    $binSize,
                ),
            ],
            'storage'  => [
                'loki'   => $this->aggregateLatestNumeric(
                    self::COLLECTION_LOKI_METRICS,
                    'total_data_size_mb',
                    'timestamp',
                    $dateFrom,
                    $dateTo,
                    $binSize,
                ),
                'mongo'  => $this->aggregateLatestNumeric(
                    self::COLLECTION_DB_STORAGE_METRICS,
                    'storage_size_mb',
                    'timestamp',
                    $dateFrom,
                    $dateTo,
                    $binSize,
                ),
                'rabbit' => $this->aggregateLatestNumeric(
                    self::COLLECTION_RABBIT_METRICS,
                    'total_disk_mb',
                    'timestamp',
                    $dateFrom,
                    $dateTo,
                    $binSize,
                ),
            ],
        ];
    }

    /**
     * Map a usage payload to a list of resource bands that are currently >= warning,
     * suitable for triggering Notifier events. Returns one entry per affected resource.
     *
     * @param mixed[] $usage
     *
     * @return mixed[]
     */
    public static function bandsToReport(array $usage): array
    {
        $out = [];
        foreach (['messages', 'storage'] as $resource) {
            $band = $usage['band'][$resource] ?? self::BAND_NONE;
            if ($band === self::BAND_NONE) {
                continue;
            }
            $out[] = [
                'band'     => $band,
                'current'  => $resource === 'messages'
                    ? ($usage['usage']['messages'] ?? 0)
                    : ($usage['usage']['storageMb'] ?? 0),
                'limit'    => $resource === 'messages'
                    ? ($usage['limits']['messages'] ?? 0)
                    : ($usage['limits']['storageGb'] ?? 0) * 1_024.0,
                'percent'  => $usage['percent'][$resource] ?? NULL,
                'resource' => $resource,
            ];
        }

        return $out;
    }

    /**
     * @param int|float $current
     * @param int|float $limit
     *
     * @return float|null Percentage (0..N) or null when the limit is unset/<=0 (unlimited).
     */
    public static function percent(int|float $current, int|float $limit): ?float
    {
        if ($limit <= 0) {
            return NULL;
        }

        return round((float) $current / (float) $limit * 100, 1);
    }

    /**
     * @param int|float $current
     * @param int|float $limit
     *
     * @return string
     */
    public static function band(int|float $current, int|float $limit): string
    {
        if ($limit <= 0) {
            return self::BAND_NONE;
        }
        $pct = (float) $current / (float) $limit * 100;

        if ($pct >= 100) {
            return self::BAND_EXCEEDED;
        }
        if ($pct >= self::THRESHOLD_CRITICAL_PCT) {
            return self::BAND_CRITICAL;
        }
        if ($pct >= self::THRESHOLD_WARNING_PCT) {
            return self::BAND_WARNING;
        }

        return self::BAND_NONE;
    }

    /**
     * Pure pipeline builder for {@see aggregateLatestNumeric}. Extracted as a
     * static helper so the shape can be asserted in unit tests without a live
     * Mongo connection.
     *
     * @param string      $valueField
     * @param string      $timeField
     * @param UTCDateTime $from
     * @param UTCDateTime $to
     * @param int         $binSize
     * @param bool        $sumAcrossSamples
     *
     * @return mixed[]
     */
    public static function buildLatestNumericPipeline(
        string $valueField,
        string $timeField,
        UTCDateTime $from,
        UTCDateTime $to,
        int $binSize,
        bool $sumAcrossSamples,
    ): array
    {
        $fromMs = (int) (string) $from;

        $pipeline = [
            ['$match' => [$timeField => ['$gte' => $from, '$lt' => $to]]],
            ['$project' => [
                'bucket' => [
                    '$add' => [
                        $fromMs,
                        ['$multiply' => [
                            ['$floor' => [
                                ['$divide' => [
                                    ['$subtract' => [['$toLong' => sprintf('$%s', $timeField)], $fromMs]],
                                    $binSize,
                                ]],
                            ]],
                            $binSize,
                        ]],
                    ],
                ],
                'time'   => sprintf('$%s', $timeField),
                'value'  => ['$ifNull' => [sprintf('$%s', $valueField), 0]],
            ]],
        ];

        if ($sumAcrossSamples) {
            // Truncate per-row time to the minute so per-node samples emitted
            // within the same tick collapse into one composite key, then sum
            // their values. The collector writes one row per node per tick,
            // so this yields the cluster-wide total per minute.
            $pipeline[] = ['$group' => [
                'value' => ['$sum' => '$value'],
                '_id'   => [
                    'bucket' => '$bucket',
                    'tick'   => ['$dateTrunc' => ['date' => '$time', 'unit' => 'minute']],
                ],
            ]];
            $pipeline[] = ['$sort' => ['_id.tick' => 1]];
            $pipeline[] = ['$group' => [
                'value' => ['$last' => '$value'],
                '_id'   => '$_id.bucket',
            ]];
        } else {
            $pipeline[] = ['$sort' => ['time' => 1]];
            $pipeline[] = ['$group' => [
                'value' => ['$last' => '$value'],
                '_id'   => '$bucket',
            ]];
        }

        $pipeline[] = ['$sort' => ['_id' => 1]];
        $pipeline[] = ['$project' => [
            'created' => ['$dateToString' => ['format' => '%Y-%m-%dT%H:%M:%SZ', 'date' => ['$toDate' => '$_id']]],
            'value'   => ['$round' => ['$value', 2]],
            '_id'     => 0,
        ]];

        return $pipeline;
    }

    /**
     * @param string $collection
     *
     * @return mixed[]
     */
    private function fetchLatestMetric(string $collection): array
    {
        try {
            $cursor = $this->metricsCollection($collection)->find(
                [],
                ['sort' => ['timestamp' => -1], 'limit' => 1, 'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']],
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
     * @param string $collection
     *
     * @return Collection
     */
    private function metricsCollection(string $collection): Collection
    {
        // LimiterMetrics is mapped to the metrics DB - we re-use its database
        // handle to obtain a raw MongoDB Collection for the collector-managed
        // collections that have no Doctrine document class.
        return $this->metricsDm->getDocumentDatabase(LimiterMetrics::class)->selectCollection($collection);
    }

    /**
     * @param UTCDateTime $from
     * @param UTCDateTime $to
     * @param int         $binSize
     *
     * @return mixed[]
     */
    private function aggregateMessagesHistory(UTCDateTime $from, UTCDateTime $to, int $binSize): array
    {
        $rabbit = $this->aggregateLatestNumeric(
            self::COLLECTION_RABBIT_METRICS,
            'total_messages',
            'timestamp',
            $from,
            $to,
            $binSize,
        );

        // The limiter time-series is written by the `limiter` service once per minute,
        // but as **N rows per tick** (one per active node/topology). A naive `$last`
        // per bucket would pick a single node's count instead of the cluster-wide
        // total. Sum across nodes per minute first, then take the latest per-bucket
        // tick — that's the value comparable to the live `countDocuments()` headline.
        $limiter = $this->aggregateLatestNumeric(
            'limiter',
            'fields.messages',
            'fields.created',
            $from,
            $to,
            $binSize,
            TRUE,
        );

        return $this->mergeSeries($rabbit, $limiter);
    }

    /**
     * @param UTCDateTime $from
     * @param UTCDateTime $to
     * @param int         $binSize
     *
     * @return mixed[]
     */
    private function aggregateStorageHistory(UTCDateTime $from, UTCDateTime $to, int $binSize): array
    {
        $rabbit = $this->aggregateLatestNumeric(
            self::COLLECTION_RABBIT_METRICS,
            'total_disk_mb',
            'timestamp',
            $from,
            $to,
            $binSize,
        );
        $mongo  = $this->aggregateLatestNumeric(
            self::COLLECTION_DB_STORAGE_METRICS,
            'storage_size_mb',
            'timestamp',
            $from,
            $to,
            $binSize,
        );
        $loki   = $this->aggregateLatestNumeric(
            self::COLLECTION_LOKI_METRICS,
            'total_data_size_mb',
            'timestamp',
            $from,
            $to,
            $binSize,
        );

        return $this->mergeSeries($rabbit, $mongo, $loki);
    }

    /**
     * Per-bucket "latest sample" for a numeric scalar field on a `timestamp`-keyed
     * metrics collection. Returns `[ ['created' => ISO, 'value' => float], ... ]`
     * sorted ascending by bucket start.
     *
     * When the source collection writes **multiple rows per tick** (e.g. the
     * limiter metrics, which are emitted per-node every minute), set
     * `$sumAcrossSamples = true`. The pipeline then collapses all rows that
     * fall into the same minute into a single per-tick sum first, and only
     * takes `$last` of those per-tick sums per bucket. Without this, `$last`
     * would return one node's count instead of the cluster-wide total.
     *
     * @param string      $collection
     * @param string      $valueField
     * @param string      $timeField
     * @param UTCDateTime $from
     * @param UTCDateTime $to
     * @param int         $binSize
     * @param bool        $sumAcrossSamples
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

            $pipeline = self::buildLatestNumericPipeline(
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
     * Sum N parallel time-series by `created`. Each input is a list of
     * `{ created, value }` items; output is one list with the per-bucket sum.
     *
     * @param mixed[] ...$series
     *
     * @return mixed[]
     */
    private function mergeSeries(array ...$series): array
    {
        $totals = [];
        foreach ($series as $rows) {
            foreach ($rows as $row) {
                $created = (string) ($row['created'] ?? '');
                if ($created === '') {
                    continue;
                }
                $totals[$created] = ($totals[$created] ?? 0.0) + (float) ($row['value'] ?? 0);
            }
        }

        ksort($totals);
        $out = [];
        foreach ($totals as $created => $value) {
            $out[] = ['created' => $created, 'value' => round($value, 2)];
        }

        return $out;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function bsonToArray(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'getArrayCopy')) {
            $value = $value->getArrayCopy();
        } elseif (is_object($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->bsonToArray($v);
            }
        }

        return $value;
    }

}
