<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Model;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\ProcessHandler;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;

/**
 * Class MetricsAggregator
 *
 * Builds compact metrics summaries the Trace assistant can ask for via MCP.
 *
 * The aggregator intentionally keeps the response shape minimal — the LLM's
 * second-pass summariser turns it into prose. Therefore each method returns
 * a flat array with `kind`, `title`, `period` plus a small payload (`points`
 * for time series, `items` for ranked lists). Cap thresholds (12 buckets,
 * 10 list items) keep the JSON small enough to feed back into the model.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Model
 */
final class MetricsAggregator
{

    private const int DEFAULT_BUCKETS = 12;
    private const int MAX_BUCKETS     = 24;
    private const int MIN_BUCKETS     = 1;
    private const int DEFAULT_LIMIT   = 10;
    private const int MAX_LIMIT       = 20;
    private const int MIN_LIMIT       = 1;

    /**
     * Maximum length of `resultMessage` we ship back to the model. Connector
     * metrics already store the upstream `response_error` body verbatim, so
     * we trim aggressively to keep the renderer output (and any later LLM
     * pass) readable.
     */
    private const int MESSAGE_TRUNCATE_AT = 240;

    /**
     * Hard cap on the post-filter pool we pull from Mongo before slicing to
     * the user-facing limit. Big enough to absorb a `topology_id` post-filter
     * without losing recent entries, small enough to keep the grid query fast.
     */
    private const int FAILED_FETCH_POOL = 200;

    /**
     * MetricsAggregator constructor.
     *
     * @param ProcessHandler  $processHandler
     * @param MetricsHandler  $metricsHandler
     * @param DocumentManager $dm
     */
    public function __construct(
        private readonly ProcessHandler $processHandler,
        private readonly MetricsHandler $metricsHandler,
        private readonly DocumentManager $dm,
    )
    {
    }

    /**
     * @param mixed[] $args
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getProcessesTimeseries(array $args): array
    {
        [$start, $end] = DateRangeResolver::resolve($args, 7);
        $end         ??= new DateTimeImmutable('now');

        $buckets = $this->clamp(
            (int) ($args['buckets'] ?? self::DEFAULT_BUCKETS),
            self::MIN_BUCKETS,
            self::MAX_BUCKETS,
        );

        $filter = [
            [
                ['column' => 'created', 'operator' => GridFilterAbstract::BETWEEN, 'value' => [
                    $start->format(DATE_ATOM),
                    $end->format(DATE_ATOM),
                ]],
            ],
        ];

        $topologyId = isset($args['topology_id']) && is_string($args['topology_id']) ? $args['topology_id'] : NULL;
        if ($topologyId !== NULL && $topologyId !== '') {
            $filter[] = [
                ['column' => 'topologyId', 'operator' => GridFilterAbstract::EQ, 'value' => [$topologyId]],
            ];
        }

        $dto = new GridRequestDto([
            GridRequestDto::FILTER => $filter,
            GridRequestDto::PAGING => [
                GridRequestDto::ITEMS_PER_PAGE => 1_000,
                GridRequestDto::PAGE           => 1,
            ],
        ]);

        $response = $this->processHandler->getProcessesGraph($dto, $buckets);
        $items    = $response[GridRequestDto::ITEMS] ?? [];

        return $this->buildProcessesTimeseries($items, $start, $end, $topologyId);
    }

    /**
     * @param mixed[] $args
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getFailingConnectors(array $args): array
    {
        [$start, $end] = DateRangeResolver::resolve($args, 7);
        $end         ??= new DateTimeImmutable('now');

        $limit = $this->clamp((int) ($args['limit'] ?? self::DEFAULT_LIMIT), self::MIN_LIMIT, self::MAX_LIMIT);

        $filter = [
            [
                ['column' => 'created', 'operator' => GridFilterAbstract::BETWEEN, 'value' => [
                    $start->format(DATE_ATOM),
                    $end->format(DATE_ATOM),
                ]],
            ],
        ];

        $dto = new GridRequestDto([
            GridRequestDto::FILTER => $filter,
            GridRequestDto::PAGING => [
                GridRequestDto::ITEMS_PER_PAGE => 200,
                GridRequestDto::PAGE           => 1,
            ],
        ]);

        $response = $this->metricsHandler->getMetricsConnectorsOverview($dto);
        $items    = $response[GridRequestDto::ITEMS] ?? [];

        return $this->buildFailingConnectors($items, $start, $end, $limit);
    }

    /**
     * Returns the most recent failed connector calls in the requested
     * window. Sourced from `ConnectorsMetrics` (the same collection that
     * powers the dashboard process detail view), so each item carries the
     * upstream HTTP status, the truncated `response_error` body and the
     * timing of the failed call. We do NOT touch Loki here — connector
     * metrics already capture every 4xx/5xx outcome the bridge observed.
     *
     * Soft SDK outcomes (`repeat`, `limit`, `trashed` without an HTTP call)
     * are NOT covered by this view. They live only in audit checkpoints in
     * Loki; if a future use-case needs them, expose a separate tool rather
     * than mixing the two telemetry streams.
     *
     * @param mixed[] $args
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getRecentErrors(array $args): array
    {
        [$start, $end] = DateRangeResolver::resolve($args, 7);
        $end         ??= new DateTimeImmutable('now');

        $limit      = $this->clamp((int) ($args['limit'] ?? self::DEFAULT_LIMIT), self::MIN_LIMIT, self::MAX_LIMIT);
        $topologyId = isset($args['topology_id']) && is_string($args['topology_id']) && $args['topology_id'] !== ''
            ? $args['topology_id']
            : NULL;

        // Pull a wider pool than `limit` so the optional topology_id filter
        // (applied client-side because the grid filter doesn't expose the
        // topology_id condition) doesn't strip the user out of useful items.
        $dto = new GridRequestDto([
            GridRequestDto::FILTER => [
                [
                    [
                        'column'   => 'created',
                        'operator' => GridFilterAbstract::BETWEEN,
                        'value'    => [$start->format(DATE_ATOM), $end->format(DATE_ATOM)],
                    ],
                    [
                        'column'   => 'status',
                        'operator' => GridFilterAbstract::EQ,
                        'value'    => ['FAILED'],
                    ],
                ],
            ],
            GridRequestDto::PAGING => [
                GridRequestDto::ITEMS_PER_PAGE => self::FAILED_FETCH_POOL,
                GridRequestDto::PAGE           => 1,
            ],
            GridRequestDto::SORTER => [
                [GridFilterAbstract::COLUMN => 'created', GridFilterAbstract::DIRECTION => GridFilterAbstract::DESCENDING],
            ],
        ]);

        $response = $this->metricsHandler->getMetricsConnectors($dto);
        $rows     = $response[GridRequestDto::ITEMS] ?? [];

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rowTopologyId = (string) ($row['topologyId'] ?? '');
            if ($topologyId !== NULL && $rowTopologyId !== $topologyId) {
                continue;
            }

            $rowNodeId = (string) ($row['nodeId'] ?? '');
            $items[]   = [
                'correlationId' => (string) ($row['correlationId'] ?? ''),
                'finishedAt'    => (string) ($row['created'] ?? ''),
                'httpStatus'    => isset($row['status']) ? (int) $row['status'] : NULL,
                'nodeName'      => $this->resolveNodeName($rowNodeId) ?? $rowNodeId,
                'resultMessage' => $this->truncate((string) ($row['message'] ?? '')),
                'resultStatus'  => 'failed',
                'topologyId'    => $rowTopologyId,
                'topologyName'  => $this->resolveTopologyName($rowTopologyId) ?? $rowTopologyId,
            ];

            if (count($items) >= $limit) {
                break;
            }
        }

        return [
            'items'  => $items,
            'kind'   => 'list',
            'period' => sprintf('%s..%s', $start->format(DATE_ATOM), $end->format(DATE_ATOM)),
            'title'  => $topologyId !== NULL
                ? sprintf('Recent errors (topology %s)', $topologyId)
                : 'Recent errors',
        ];
    }

    /**
     * @param string $message
     *
     * @return string
     */
    private function truncate(string $message): string
    {
        if ($message === '') {
            return '';
        }

        if (mb_strlen($message) <= self::MESSAGE_TRUNCATE_AT) {
            return $message;
        }

        return sprintf('%s…', mb_substr($message, 0, self::MESSAGE_TRUNCATE_AT - 1));
    }

    /**
     * @param mixed[]           $items
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     * @param string|null       $topologyId
     *
     * @return mixed[]
     */
    private function buildProcessesTimeseries(
        array $items,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        ?string $topologyId,
    ): array
    {
        $byTime = [];

        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }

            $time = (string) ($row['created'] ?? '');
            if ($time === '') {
                continue;
            }

            if (!isset($byTime[$time])) {
                $byTime[$time] = ['failed' => 0, 'success' => 0, 'time' => $time];
            }

            $byTime[$time]['success'] += (int) ($row['success'] ?? 0);
            $byTime[$time]['failed']  += (int) ($row['failed'] ?? 0);
        }

        ksort($byTime);
        $points = array_values($byTime);

        $totalSuccess = array_sum(array_column($points, 'success'));
        $totalFailed  = array_sum(array_column($points, 'failed'));

        return [
            'failed'     => $totalFailed,
            'kind'       => 'timeseries',
            'period'     => sprintf('%s..%s', $start->format(DATE_ATOM), $end->format(DATE_ATOM)),
            'points'     => $points,
            'success'    => $totalSuccess,
            'title'      => $topologyId !== NULL
                ? sprintf('Processes (topology %s)', $topologyId)
                : 'Processes (all topologies)',
            'topologyId' => $topologyId,
            'total'      => $totalSuccess + $totalFailed,
        ];
    }

    /**
     * @param mixed[]           $items
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     * @param int               $limit
     *
     * @return mixed[]
     */
    private function buildFailingConnectors(
        array $items,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $limit,
    ): array
    {
        $rows = [];

        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }

            $count   = (int) ($row['count'] ?? 0);
            $status4 = (int) ($row['status400'] ?? 0);
            $status5 = (int) ($row['status500'] ?? 0);
            $failed  = $status4 + $status5;
            if ($failed === 0) {
                continue;
            }

            $success     = max(0, $count - $failed);
            $failureRate = $count > 0 ? round($failed / $count, 4) : 0.0;
            $nodeId      = (string) ($row['nodeId'] ?? '');
            $topologyId  = (string) ($row['topologyId'] ?? '');

            $rows[] = [
                'failed'       => $failed,
                'failureRate'  => $failureRate,
                'nodeId'       => $nodeId,
                'nodeName'     => $this->resolveNodeName($nodeId) ?? $nodeId,
                'success'      => $success,
                'topologyId'   => $topologyId,
                'topologyName' => $this->resolveTopologyName($topologyId) ?? $topologyId,
            ];
        }

        usort($rows, static fn(array $a, array $b): int => $b['failed'] <=> $a['failed']);
        $rows = array_slice($rows, 0, $limit);

        return [
            'items'  => $rows,
            'kind'   => 'list',
            'period' => sprintf('%s..%s', $start->format(DATE_ATOM), $end->format(DATE_ATOM)),
            'title'  => 'Top failing connectors',
        ];
    }

    /**
     * @param string $nodeId
     *
     * @return string|null
     */
    private function resolveNodeName(string $nodeId): ?string
    {
        if ($nodeId === '') {
            return NULL;
        }

        /** @var Node|null $node */
        $node = $this->dm->getRepository(Node::class)->find($nodeId);

        return $node?->getName();
    }

    /**
     * @param string $topologyId
     *
     * @return string|null
     */
    private function resolveTopologyName(string $topologyId): ?string
    {
        if ($topologyId === '') {
            return NULL;
        }

        /** @var Topology|null $topology */
        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);

        return $topology?->getName();
    }

    /**
     * @param int $value
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

}
