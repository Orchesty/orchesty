<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Query\Builder;
use Hanaboso\CommonsBundle\Database\Document\Node;
use Hanaboso\CommonsBundle\Database\Document\Topology;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\CommonsBundle\Utils\GeneratorUtils;
use Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;
use Hanaboso\PipesFramework\Metrics\Document\MonolithMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ProcessesMetrics;
use Hanaboso\PipesFramework\Metrics\Document\RabbitMetrics;
use Hanaboso\PipesFramework\Metrics\Document\Tags;
use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use Hanaboso\PipesFramework\Metrics\Retention\RetentionFactory;
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
    private $metricsDm;

    /**
     * @var int
     */
    private $rabbitInterval;

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
     * @param array    $params
     *
     * @return array
     * @throws DateTimeException
     */
    public function getNodeMetrics(Node $node, Topology $topology, array $params): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($params);

        $where = [
            self::NODE  => $node->getId(),
            self::QUEUE => GeneratorUtils::generateQueueName($topology, $node),
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
     * @param array    $params
     *
     * @return array
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
     * @param array    $params
     *
     * @return array
     * @throws DateTimeException
     * @throws MongoDBException
     */
    public function getTopologyRequestCountMetrics(Topology $topology, array $params): array
    {
        $params['from'] = $params['from'] ?? 'now - 1h';
        $params['to']   = $params['to'] ?? 'now';
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
     * @param array  $where
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return MetricsDto
     * @throws DateTimeException
     */
    private function connectorNodeMetrics(
        array $where,
        string $dateFrom,
        string $dateTo
    ): MetricsDto
    {
        $qb = $this->metricsDm->createQueryBuilder(ConnectorsMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, ConnectorsMetrics::class);

        /** @var array $res */
        $res = $qb
            ->mapReduce(
                'function() {
                    emit(null, this.fields.sent_request_total_duration);
                }',
                'function(k, vals) {
                    return {
                        request_count: vals.length,
                        request_sum: Array.sum(vals),
                        request_max: Math.max(...vals),
                        request_min: Math.min(...vals),
                    };
                }'
            )
            ->finalize(
                'function(k, res) {
                    if (typeof res === "object") {
                        return res;
                    }
                    
                    return {
                        request_count: 1,
                        request_sum: res,
                        request_max: res,
                        request_min: res,
                    };
                }'
            )
            ->getQuery()
            ->getSingleResult();

        if (!$res) {
            $res = [
                'request_count' => 0,
                'request_sum'   => 0,
                'request_max'   => 0,
                'request_min'   => 0,
            ];
        } else {
            $res = $res['value'];
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
     * @param array  $where
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return MetricsDto
     * @throws DateTimeException
     */
    private function rabbitNodeMetrics(
        array $where,
        string $dateFrom,
        string $dateTo
    ): MetricsDto
    {
        $qb = $this->metricsDm->createQueryBuilder(RabbitMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, RabbitMetrics::class);

        /** @var array $res */
        $res = $qb
            ->mapReduce(
                'function() {
                    emit(null, this.fields.messages);
                }',
                'function(k, vals) {
                    return {
                        queue_max: Math.max(...vals),
                        queue_sum: Array.sum(vals),
                    };
                }'
            )
            ->finalize(
                'function(k, res) {
                    if (typeof res === "object") {
                        return res;
                    }
                    
                    return {
                        queue_max: res,
                        queue_sum: res,
                    };
                }'
            )
            ->getQuery()
            ->getSingleResult();

        if (!$res) {
            $res = [
                'queue_max' => 0,
                'queue_sum' => 0,
            ];
        } else {
            $res = $res['value'];
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
     * @param array  $where
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return MetricsDto
     * @throws DateTimeException
     */
    private function monolithNodeMetrics(
        array $where,
        string $dateFrom,
        string $dateTo
    ): MetricsDto
    {
        $qb = $this->metricsDm->createQueryBuilder(MonolithMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, MonolithMetrics::class);

        /** @var array $res */
        $res = $qb
            ->mapReduce(
                'function() {
                    emit(null, this.fields.fpm_cpu_kernel_time);
                }',
                'function(k, vals) {
                    return {
                        cpu_count: vals.length,
                        cpu_sum: Array.sum(vals),
                        cpu_max: Math.max(...vals),
                        cpu_min: Math.min(...vals),
                    };
                }'
            )
            ->finalize(
                'function(k, res) {
                    if (typeof res === "object") {
                        return res;
                    }
                    
                    return {
                        cpu_count: 1,
                        cpu_sum: res,
                        cpu_max: res,
                        cpu_min: res,
                    };
                }'
            )
            ->getQuery()
            ->getSingleResult();

        if (!$res) {
            $res = [
                'cpu_count' => 0,
                'cpu_sum'   => 0,
                'cpu_max'   => 0,
                'cpu_min'   => 0,
            ];
        } else {
            $res = $res['value'];
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
     * @param array  $where
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     * @throws DateTimeException
     */
    private function counterProcessMetrics(
        array $where,
        string $dateFrom,
        string $dateTo
    ): array
    {
        $qb = $this->metricsDm->createQueryBuilder(ProcessesMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, ProcessesMetrics::class);

        /** @var array $res */
        $res = $qb
            ->mapReduce(
                'function() {
                    emit(null, this.fields);
                }',
                'function(k, vals) {
                    let res = {
                        process_time_count: 0,
                        process_time_sum: 0,
                        process_time_max: 0,
                        total_count: 0,
                        request_error_sum: 0,
                        process_time_min: vals[0].counter_process_duration,
                    };

                    vals.forEach(x => {
                        res.process_time_count++;    
                        res.process_time_sum += x.counter_process_duration;
                        res.process_time_max = Math.max(res.process_time_max, x.counter_process_duration);
                        res.total_count++;
                        if (!x.counter_process_result) {
                            res.request_error_sum++;
                        }
                        res.process_time_min = Math.min(res.process_time_min, x.counter_process_duration);
                    });
                    
                    return res;
                }'
            )
            ->finalize(
                'function(k, res) {
                    if (res.hasOwnProperty("total_count")) {
                        return res;
                    }
                    
                    return {
                        process_time_count: 1,
                        process_time_sum: res.counter_process_duration,
                        process_time_min: res.counter_process_duration,
                        process_time_max: res.counter_process_duration,
                        total_count: 1,
                        request_error_sum: res.counter_process_result ? 0 : 1,
                    };
                }'
            )
            ->getQuery()
            ->getSingleResult();

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
            $res = $res['value'];
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
     * @param array  $where
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     * @throws DateTimeException
     */
    private function bridgesNodeMetrics(
        array $where,
        string $dateFrom,
        string $dateTo
    ): array
    {
        $qb = $this->metricsDm->createQueryBuilder(BridgesMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, BridgesMetrics::class);

        /** @var array $res */
        $res = $qb
            ->mapReduce(
                'function() {
                    emit(null, this.fields);
                }',
                'function(k, vals) {
                    let res = {
                        top_processed_count: 0,
                        wait_count: 0,
                        top_processed_sum: 0,
                        wait_sum: 0,
                        request_error_sum: 0,
                        total_count: 0,
                        top_processed_max: 0,
                        wait_max: 0,
                        wait_min: vals[0].bridge_job_waiting_duration,
                        top_processed_min: vals[0].bridge_job_total_duration,
                    };
                    
                    vals.forEach(x => {
                        const waiting = x.bridge_job_waiting_duration ? x.bridge_job_waiting_duration : 0;
                    
                        res.top_processed_count++;
                        res.wait_count++;
                        res.wait_sum += waiting;
                        res.top_processed_sum += x.bridge_job_total_duration;
                        res.total_count++;
                        if (!x.bridge_job_result_success) {
                            res.request_error_sum++;
                        }
                        res.top_processed_max = Math.max(res.top_processed_max, x.bridge_job_total_duration);
                        res.top_processed_min = Math.min(res.top_processed_min, x.bridge_job_total_duration);
                        res.wait_max = Math.max(res.wait_max, waiting);
                        res.wait_min = Math.min(res.wait_min, waiting);
                    });
                
                    return res;
                }'
            )
            ->finalize(
                'function(k, res) {
                    if (res.hasOwnProperty("wait_count")) {
                        return res;
                    }
                    
                    return {
                        top_processed_count: 1,
                        wait_count: (res.bridge_job_waiting_duration ? res.bridge_job_waiting_duration : 0) > 0 ? 1 : 0,
                        top_processed_sum: res.bridge_job_total_duration,
                        wait_sum: res.bridge_job_waiting_duration ? res.bridge_job_waiting_duration : 0,
                        request_error_sum: res.bridge_job_result_success ? 0 : 1,
                        total_count: 1,
                        top_processed_max: res.bridge_job_total_duration,
                        wait_max: res.bridge_job_waiting_duration ? res.bridge_job_waiting_duration : 0,
                        wait_min: res.bridge_job_waiting_duration ? res.bridge_job_waiting_duration : 0, 
                        top_processed_min: res.bridge_job_total_duration, 
                    };
                }'
            )
            ->getQuery()
            ->getSingleResult();

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
            $res = $res['value'];
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
     * @param array  $where
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     * @throws DateTimeException
     */
    private function requestsCountAggregation(
        array $where,
        string $dateFrom,
        string $dateTo
    ): array
    {
        $dateTimeFrom = DateTimeUtils::getUTCDateTime($dateFrom);
        $dateTimeTo   = DateTimeUtils::getUTCDateTime($dateTo);

        $qb = $this->metricsDm->createQueryBuilder(ProcessesMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, ProcessesMetrics::class);
        $ret = RetentionFactory::getRetentionInSeconds($dateTimeFrom, $dateTimeTo);

        $res = $qb
            ->mapReduce(
                sprintf(
                    'function() {
                    const time = this.fields.created;
                
                    emit(time - time %% %s, 1);
                }',
                    $ret
                ),
                'function(k, vals) {
                    return vals.length;
                }'
            )
            ->getQuery()
            ->execute()
            ->toArray();

        /** @var array $res */
        $res = array_combine(
            array_column($res, '_id'),
            array_column($res, 'value'),
        );

        $from = $dateTimeFrom->getTimestamp();
        $from -= $from % $ret;
        $to   = $dateTimeTo->getTimestamp();
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
     * @param array   $where
     * @param string  $document
     *
     * @throws DateTimeException
     */
    private function addConditions(Builder $qb, string $dateFrom, string $dateTo, array $where, string $document): void
    {
        $qb->addAnd(
            $qb->expr()
                ->field('fields.created')
                ->gte(DateTimeUtils::getUTCDateTime($dateFrom)->getTimestamp())
        );
        $qb->addAnd(
            $qb->expr()
                ->field('fields.created')
                ->lt(DateTimeUtils::getUTCDateTime($dateTo)->getTimestamp())
        );

        $tags = $this->allowedTags($document);
        foreach ($where as $field => $value) {
            if (in_array($field, $tags)) {
                $qb->addOr($qb->expr()->field(sprintf('tags.%s', $field))->equals($value));
            }
        }
    }

    /**
     * @param string $document
     *
     * @return array
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
     * @param array $params
     *
     * @return array
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
