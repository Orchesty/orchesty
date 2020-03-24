<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Manager;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;
use Hanaboso\PipesFramework\Metrics\Document\MonolithMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ProcessesMetrics;
use Hanaboso\PipesFramework\Metrics\Document\RabbitMetrics;
use Hanaboso\PipesFramework\Metrics\Document\Tags;
use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use Hanaboso\PipesFramework\Metrics\Retention\RetentionFactory;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\NodeGeneratorUtils;
use LogicException;

/**
 * Class MongoMetricsManager
 *
 * @package Hanaboso\PipesFramework\Metrics\Manager
 */
class MongoMetricsManager extends MetricsManagerAbstract
{

    /**
     * @var DocumentManager
     */
    private DocumentManager $metricsDm;

    /**
     * @var int
     */
    private int $rabbitInterval;

    /**
     * MongoMetricsManager constructor.
     *
     * @param DocumentManager $dm
     * @param string          $nodeTable
     * @param string          $fpmTable
     * @param string          $rabbitTable
     * @param string          $counterTable
     * @param string          $connectorTable
     * @param DocumentManager $metricsDm
     * @param int             $rabbitInterval
     */
    public function __construct(
        DocumentManager $dm,
        string $nodeTable,
        string $fpmTable,
        string $rabbitTable,
        string $counterTable,
        string $connectorTable,
        DocumentManager $metricsDm,
        int $rabbitInterval
    )
    {
        parent::__construct($dm, $nodeTable, $fpmTable, $rabbitTable, $counterTable, $connectorTable);

        $this->metricsDm      = $metricsDm;
        $this->rabbitInterval = $rabbitInterval;
    }

    /**
     * @param Node     $node
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getNodeMetrics(Node $node, Topology $topology, array $params): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($params);

        $where = [
            self::NODE  => $node->getId(),
            self::QUEUE => NodeGeneratorUtils::generateQueueName($topology->getId(), $node->getId(), $node->getName()),
        ];

        $queue   = $this->rabbitNodeMetrics($where, $dateFrom, $dateTo);
        $request = $this->connectorNodeMetrics($where, $dateFrom, $dateTo);
        $cpu     = $this->monolithNodeMetrics($where, $dateFrom, $dateTo);

        [$processTime, $waitingTime, $error] = $this->bridgesNodeMetrics($where, $dateFrom, $dateTo);

        return $this->generateOutput(
            $queue,
            $waitingTime,
            $processTime,
            $cpu,
            $request,
            $error,
            new MetricsDto()
        );
    }

    /**
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getTopologyProcessTimeMetrics(Topology $topology, array $params): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($params);

        $where = [self::TOPOLOGY => $topology->getId()];

        [$process, $error] = $this->counterProcessMetrics($where, $dateFrom, $dateTo);

        return $this->generateOutput(
            new MetricsDto(),
            new MetricsDto(),
            new MetricsDto(),
            new MetricsDto(),
            new MetricsDto(),
            $error,
            $process
        );
    }

    /**
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getTopologyRequestCountMetrics(Topology $topology, array $params): array
    {
        $params['from'] ??= 'now - 1h';
        $params['to']   ??= 'now';

        [$dateFrom, $dateTo] = $this->parseDateRange($params);

        $where = [self::TOPOLOGY => $topology->getId()];

        $res             = $this->getTopologyMetrics($topology, $params);
        $res['requests'] = $this->requestsCountAggregation($where, $dateFrom, $dateTo);

        return $res;
    }

    /**
     * -------------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param mixed[] $where
     * @param string  $dateFrom
     * @param string  $dateTo
     *
     * @return MetricsDto
     * @throws DateTimeException
     */
    private function connectorNodeMetrics(array $where, string $dateFrom, string $dateTo): MetricsDto
    {
        $qb = $this->metricsDm->createAggregationBuilder(ConnectorsMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, ConnectorsMetrics::class);
        $res = $qb->group()->field('id')->ifNull(NULL, '')
            ->field('request_sum')->sum('$fields.sent_request_total_duration')
            ->field('request_count')->sum(1)
            ->field('request_max')->max('$fields.sent_request_total_duration')
            ->field('request_min')->min('$fields.sent_request_total_duration')
            ->execute()
            ->toArray();

        if (!$res) {
            $res = [
                'request_count' => 0,
                'request_sum'   => 0,
                'request_max'   => 0,
                'request_min'   => 0,
            ];
        } else {
            $res = reset($res);
        }

        return (new MetricsDto())
            ->setMin($res[self::REQUEST_MIN])
            ->setMax($res[self::REQUEST_MAX])
            ->setTotal($res[self::REQUEST_COUNT])
            ->setAvg(
                $res[self::REQUEST_COUNT],
                $res[self::REQUEST_SUM]
            );
    }

    /**
     * @param mixed[] $where
     * @param string  $dateFrom
     * @param string  $dateTo
     *
     * @return MetricsDto
     * @throws DateTimeException
     */
    private function rabbitNodeMetrics(array $where, string $dateFrom, string $dateTo): MetricsDto
    {
        $qb = $this->metricsDm->createAggregationBuilder(RabbitMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, RabbitMetrics::class);
        $res = $qb->group()->field('id')->ifNull(NULL, '')
            ->field('queue_max')->max('$fields.messages')
            ->field('queue_sum')->sum('$fields.messages')
            ->execute()
            ->toArray();

        if (!$res) {
            $res = [
                'queue_max' => 0,
                'queue_sum' => 0,
            ];
        } else {
            $res = reset($res);
        }

        $from = DateTimeUtils::getUtcDateTime($dateFrom);
        $to   = DateTimeUtils::getUtcDateTime($dateTo);

        $diff = $to->diff($from);
        $secs = (($diff->days * 24 + $diff->h) * 60 + $diff->i) * 60 + $diff->s;

        $res[self::QUEUE_COUNT] = $secs / $this->rabbitInterval;

        return (new MetricsDto())
            ->setMax($res[self::QUEUE_MAX])
            ->setTotal($res[self::QUEUE_COUNT])
            ->setAvg(
                $res[self::QUEUE_COUNT],
                $res[self::QUEUE_SUM]
            );
    }

    /**
     * @param mixed[] $where
     * @param string  $dateFrom
     * @param string  $dateTo
     *
     * @return MetricsDto
     * @throws DateTimeException
     */
    private function monolithNodeMetrics(array $where, string $dateFrom, string $dateTo): MetricsDto
    {
        $qb = $this->metricsDm->createAggregationBuilder(MonolithMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, MonolithMetrics::class);
        $res = $qb->group()->field('id')->ifNull(NULL, '')
            ->field('cpu_sum')->sum('$fields.fpm_cpu_kernel_time')
            ->field('cpu_count')->sum(1)
            ->field('cpu_max')->max('$fields.fpm_cpu_kernel_time')
            ->field('cpu_min')->min('$fields.fpm_cpu_kernel_time')
            ->execute()
            ->toArray();

        if (!$res) {
            $res = [
                'cpu_count' => 0,
                'cpu_sum'   => 0,
                'cpu_max'   => 0,
                'cpu_min'   => 0,
            ];
        } else {
            $res = reset($res);
        }

        return (new MetricsDto())
            ->setMin($res[self::CPU_MIN])
            ->setMax($res[self::CPU_MAX])
            ->setTotal($res[self::CPU_COUNT])
            ->setAvg(
                $res[self::CPU_COUNT],
                $res[self::CPU_SUM]
            );
    }

    /**
     * @param mixed[] $where
     * @param string  $dateFrom
     * @param string  $dateTo
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    private function counterProcessMetrics(array $where, string $dateFrom, string $dateTo): array
    {
        $qb = $this->metricsDm->createAggregationBuilder(ProcessesMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, ProcessesMetrics::class);
        $res = $qb->group()->field('id')->ifNull(NULL, '')
            ->field('process_time_sum')->sum('$fields.counter_process_duration')
            ->field('process_time_count')->sum(1)
            ->field('process_time_max')->max('$fields.counter_process_duration')
            ->field('process_time_min')->min('$fields.counter_process_duration')
            ->field('total_count')->sum(1)
            ->field('request_error_sum')->sum(
                $qb->expr()->cond(
                    $qb->expr()->eq('$fields.counter_process_result', FALSE),
                    1,
                    0
                )
            )
            ->execute()
            ->toArray();

        if (!$res) {
            $res = [
                'process_time_count' => 0,
                'process_time_sum'   => 0,
                'process_time_min'   => 0,
                'process_time_max'   => 0,
                'total_count'        => 0,
                'request_error_sum'  => 0,
            ];
        } else {
            $res = reset($res);
        }

        // Process time, Error
        return [
            (new MetricsDto())
                ->setMin($res[self::PROCESS_TIME_MIN])
                ->setMax($res[self::PROCESS_TIME_MAX])
                ->setTotal($res[self::PROCESS_TIME_COUNT])
                ->setAvg(
                    $res[self::PROCESS_TIME_COUNT],
                    $res[self::PROCESS_TIME_SUM]
                ),
            (new MetricsDto())
                ->setTotal($res[self::NODE_TOTAL_SUM])
                ->setErrors($res[self::NODE_ERROR_SUM]),
        ];
    }

    /**
     * @param mixed[] $where
     * @param string  $dateFrom
     * @param string  $dateTo
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    private function bridgesNodeMetrics(array $where, string $dateFrom, string $dateTo): array
    {
        $qb = $this->metricsDm->createAggregationBuilder(BridgesMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, BridgesMetrics::class);
        $res = $qb->group()->field('id')->ifNull(NULL, '')
            ->field('top_processed_sum')->sum('$fields.bridge_job_total_duration')
            ->field('top_processed_count')->sum(1)
            ->field('top_processed_max')->max('$fields.bridge_job_total_duration')
            ->field('top_processed_min')->min('$fields.bridge_job_total_duration')
            ->field('wait_sum')->sum('$fields.bridge_job_waiting_duration')
            ->field('wait_count')->sum(1)
            ->field('wait_max')->max('$fields.bridge_job_waiting_duration')
            ->field('wait_min')->min(
                $qb->expr()->ifNull('$fields.bridge_job_waiting_duration', 0)
            )
            ->field('total_count')->sum(1)
            ->field('request_error_sum')->sum(
                $qb->expr()->cond(
                    $qb->expr()->eq('$fields.bridge_job_result_success', FALSE),
                    1,
                    0
                )
            )
            ->execute()
            ->toArray();

        if (!$res) {
            $res = [
                'top_processed_count' => 0,
                'wait_count'          => 0,
                'top_processed_sum'   => 0,
                'wait_sum'            => 0,
                'request_error_sum'   => 0,
                'total_count'         => 0,
                'top_processed_max'   => 0,
                'wait_max'            => 0,
                'wait_min'            => 0,
                'top_processed_min'   => 0,
            ];
        } else {
            $res = reset($res);
        }

        // Process time, Waiting time, Error
        return [
            (new MetricsDto())
                ->setMin($res[self::PROCESSED_MIN])
                ->setMax($res[self::PROCESSED_MAX])
                ->setAvg(
                    $res[self::PROCESSED_COUNT],
                    $res[self::PROCESSED_SUM]
                ),
            (new MetricsDto())
                ->setMin($res[self::WAIT_MIN])
                ->setMax($res[self::WAIT_MAX])
                ->setAvg(
                    $res[self::WAIT_COUNT],
                    $res[self::WAIT_SUM]
                ),
            (new MetricsDto())
                ->setTotal($res[self::NODE_TOTAL_SUM])
                ->setErrors($res[self::NODE_ERROR_SUM]),
        ];
    }

    /**
     * @param mixed[] $where
     * @param string  $dateFrom
     * @param string  $dateTo
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    private function requestsCountAggregation(array $where, string $dateFrom, string $dateTo): array
    {
        $dateTimeFrom = DateTimeUtils::getUtcDateTime($dateFrom);
        $dateTimeTo   = DateTimeUtils::getUtcDateTime($dateTo);

        $qb = $this->metricsDm->createAggregationBuilder(ProcessesMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, ProcessesMetrics::class);
        $ret = RetentionFactory::getRetentionInSeconds($dateTimeFrom, $dateTimeTo);

        $res = $qb
            ->group()->field('id')
            ->subtract('$fields.created', $qb->expr()->mod('$fields.created', $ret))
            ->field('count')->sum(1)
            ->execute()
            ->toArray();

        /** @var mixed[] $res */
        $res = array_combine(
            array_column($res, '_id'),
            array_column($res, 'count'),
        );

        $from  = $dateTimeFrom->getTimestamp();
        $from -= $from % $ret;
        $to    = $dateTimeTo->getTimestamp();
        $to   -= $to % $ret;

        $sorted = [];
        for ($i = $from; $i <= $to; $i += $ret) {
            $sorted[$i] = (int) ($res[$i] ?? 0);
        }

        return $sorted;
    }

    /**
     * @param Builder $qb
     * @param string  $dateFrom
     * @param string  $dateTo
     * @param mixed[] $where
     * @param string  $document
     *
     * @throws DateTimeException
     */
    private function addConditions(Builder $qb, string $dateFrom, string $dateTo, array $where, string $document): void
    {
        $qb->match()
            ->addAnd(
                $qb->matchExpr()
                    ->field('fields.created')
                    ->gte(DateTimeUtils::getUtcDateTime($dateFrom)->getTimestamp()),
                $qb->matchExpr()
                    ->field('fields.created')
                    ->lt(DateTimeUtils::getUtcDateTime($dateTo)->getTimestamp())
            );

        $tags = $this->allowedTags($document);
        foreach ($where as $field => $value) {
            if (in_array($field, $tags, TRUE)) {
                $qb->match()->addOr($qb->matchExpr()->field(sprintf('tags.%s', $field))->equals($value));
            }
        }
    }

    /**
     * @param string $document
     *
     * @return mixed[]
     */
    private function allowedTags(string $document): array
    {
        switch ($document) {
            case BridgesMetrics::class:
                return Tags::BRIDGE_TAGS;
            case RabbitMetrics::class:
                return Tags::RABBIT_TAGS;
            case ConnectorsMetrics::class:
                return Tags::CONNECTOR_TAGS;
            case MonolithMetrics::class:
                return Tags::MONOLITH_TAGS;
            default:
                return [];
        }
    }

    /**
     * @param mixed[] $params
     *
     * @return mixed[]
     */
    private function parseDateRange(array $params): array
    {
        /** @var string|null $dateFrom */
        $dateFrom = $params['from'] ?? NULL;
        /** @var string|null $dateTo */
        $dateTo = $params['to'] ?? NULL;
        if (!$dateFrom || !$dateTo) {
            throw new LogicException('Date range, fields: [from, to] are required.');
        }

        return [$dateFrom, $dateTo];
    }

}
