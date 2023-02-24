<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Manager;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ContainerMetrics;
use Hanaboso\PipesFramework\Metrics\Document\MonolithMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ProcessesMetrics;
use Hanaboso\PipesFramework\Metrics\Document\RabbitConsumerMetrics;
use Hanaboso\PipesFramework\Metrics\Document\RabbitMetrics;
use Hanaboso\PipesFramework\Metrics\Document\Tags;
use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use Hanaboso\PipesFramework\Metrics\Enum\HealthcheckTypeEnum;
use Hanaboso\PipesFramework\Metrics\Enum\ServiceNameByQueueEnum;
use Hanaboso\PipesFramework\Metrics\Retention\RetentionFactory;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use LogicException;
use MongoDB\BSON\Regex;

/**
 * Class MongoMetricsManager
 *
 * @package Hanaboso\PipesFramework\Metrics\Manager
 */
final class MongoMetricsManager extends MetricsManagerAbstract
{

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
     * @param string          $consumerTable
     */
    public function __construct(
        private DocumentManager $dm,
        string $nodeTable,
        string $fpmTable,
        string $rabbitTable,
        string $counterTable,
        string $connectorTable,
        private DocumentManager $metricsDm,
        string $consumerTable,
    )
    {
        parent::__construct($dm, $nodeTable, $fpmTable, $rabbitTable, $counterTable, $connectorTable, $consumerTable);
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
        $topology;
        [$dateFrom, $dateTo] = $this->parseDateRange($params);

        $where = [
            self::NODE => $node->getId(),
        ];

        $queue   = (new MetricsDto())->setMax(0)->setTotal(0)->setAvg(0, 0);
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
            new MetricsDto(),
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
            $process,
        );
    }

    /**
     * @param mixed[] $params
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getTopologiesProcessTimeMetrics(array $params): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($params);

        [$process, $error] = $this->counterProcessMetrics([], $dateFrom, $dateTo);

        return $this->generateOutput(
            new MetricsDto(),
            new MetricsDto(),
            new MetricsDto(),
            new MetricsDto(),
            new MetricsDto(),
            $error,
            $process,
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
        $params['from'] ??= 'now - 1 hours';
        $params['to']   ??= 'now';

        [$dateFrom, $dateTo] = $this->parseDateRange($params);

        $where = [self::TOPOLOGY => $topology->getId()];

        $res          = $this->getTopologyMetrics($topology, $params);
        $keepRequests = FALSE;
        foreach ($res as $v) {
            if (array_key_exists('request_time', $v)) {
                $keepRequests = TRUE;

                break;
            }
        }
        if ($keepRequests) {
            $res['requests'] = $this->requestsCountAggregation($where, $dateFrom, $dateTo);
        }

        return $res;
    }

    /**
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getHealthcheckMetrics(): array
    {
        $qb  = $this->metricsDm->createQueryBuilder(RabbitConsumerMetrics::class);
        $res = $qb
            ->field('fields.consumers')->lte(0)
            ->getQuery()
            ->toArray();

        if (!$res) {
            $res = [];
        }

        $healthcheckArray = array_map(
            function (RabbitConsumerMetrics $item): array {
                $service = ServiceNameByQueueEnum::getNameAndNodeId($item->getTags()->getQueue());
                if ($service['name'] === ServiceNameByQueueEnum::BRIDGE->value) {
                    $node = $this->dm->find(Node::class, $service['nodeId']);
                    if (!$node) {
                        throw new DocumentNotFoundException(sprintf('Node with id %s not found', $service['nodeId']));
                    }
                    $topology = $this->dm->find(Topology::class, $node->getTopology());
                    if (!$topology) {
                        throw new DocumentNotFoundException(
                            sprintf('Topology with id %s not found', $node->getTopology()),
                        );
                    }

                    $service['name'] = sprintf(
                        '%s-%s_topology-%s_1',
                        $topology->getId(),
                        $topology->getName(),
                        $topology->getId(),
                    );
                    $topology        = sprintf('%s v.%s', $topology->getName(), $topology->getVersion());
                }

                return [
                    'type'     => HealthcheckTypeEnum::QUEUE->value,
                    'name'     => $item->getTags()->getQueue(),
                    'service'  => $service['name'],
                    'topology' => $topology ?? NULL,
                ];
            },
            $res,
        );

        $qb  = $this->metricsDm->createQueryBuilder(ContainerMetrics::class);
        $res = $qb
            ->field('fields.up')->equals(FALSE)
            ->field('fields.name')->not(new Regex('wait-for-it'))
            ->getQuery()
            ->toArray();

        if (!$res) {
            $res = [];
        }

        return array_merge(
            $healthcheckArray,
            array_map(
                static fn(ContainerMetrics $item): array => [
                    'type'    => HealthcheckTypeEnum::SERVICE->value,
                    'name'    => $item->getFields()->getName(),
                    'message' => $item->getFields()->getMessage(),
                ],
                $res,
            ),
        );
    }

    /**
     * @param mixed[]     $params
     * @param string|null $key
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getApplicationMetrics(array $params, ?string $key): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($params);

        $where = [];
        if ($key) {
            $where = [self::APPLICATION => $key];
        }

        $qb = $this->metricsDm->createAggregationBuilder(ConnectorsMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, ConnectorsMetrics::class);

        $result = $qb
            ->group()
            ->field('_id')
            ->expression('$tags.correlation_id')
            ->execute()
            ->toArray();

        return ['application' => count($result)];
    }

    /**
     * @param mixed[]     $params
     * @param string|null $user
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getUserMetrics(array $params, ?string $user): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($params);

        $where = [];
        if ($user) {
            $where = [self::USER => $user];
        }

        $qb = $this->metricsDm->createAggregationBuilder(ConnectorsMetrics::class);
        $this->addConditions($qb, $dateFrom, $dateTo, $where, ConnectorsMetrics::class);

        $result = $qb
            ->group()
            ->field('_id')
            ->expression('$tags.correlation_id')
            ->execute()
            ->toArray();

        return ['user' => count($result)];
    }

    /**
     * -------------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param mixed[] $where
     * @param string  $dateFrom
     * @param string  $dateTo
     *
     * @return MetricsDto|null
     * @throws DateTimeException
     */
    private function connectorNodeMetrics(array $where, string $dateFrom, string $dateTo): MetricsDto|null
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
            return NULL;
        } else {
            $res = reset($res);
        }

        return (new MetricsDto())
            ->setMin($res[self::REQUEST_MIN])
            ->setMax($res[self::REQUEST_MAX])
            ->setTotal($res[self::REQUEST_COUNT])
            ->setAvg($res[self::REQUEST_COUNT], $res[self::REQUEST_SUM]);
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
            ->setAvg($res[self::CPU_COUNT], $res[self::CPU_SUM]);
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
        $res = $qb
            ->match()->field('parent')->equals(NULL)
            ->group()->field('id')->ifNull(NULL, '')
            ->field('process_time_sum')->sum('$fields.duration')
            ->field('process_time_count')->sum(1)
            ->field('process_time_max')->max('$fields.duration')
            ->field('process_time_min')->min('$fields.duration')
            ->field('total_count')->sum(1)
            ->field('request_error_sum')->sum(
                $qb->expr()->cond(
                    $qb->expr()->eq('$fields.fail_count', 0),
                    0,
                    1,
                ),
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
                ->setAvg($res[self::PROCESS_TIME_COUNT], $res[self::PROCESS_TIME_SUM]),
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
            ->field('top_processed_sum')->sum('$fields.total_duration')
            ->field('top_processed_count')->sum(1)
            ->field('top_processed_max')->max('$fields.total_duration')
            ->field('top_processed_min')->min('$fields.total_duration')
            ->field('wait_sum')->sum('$fields.waiting_duration')
            ->field('wait_count')->sum(1)
            ->field('wait_max')->max('$fields.waiting_duration')
            ->field('wait_min')->min(
                $qb->expr()->ifNull('$fields.waiting_duration', 0),
            )
            ->field('total_count')->sum(1)
            ->field('request_error_sum')->sum(
                $qb->expr()->cond(
                    $qb->expr()->eq('$fields.result_success', FALSE),
                    1,
                    0,
                ),
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
                ->setAvg($res[self::PROCESSED_COUNT], $res[self::PROCESSED_SUM]),
            (new MetricsDto())
                ->setMin($res[self::WAIT_MIN])
                ->setMax($res[self::WAIT_MAX])
                ->setAvg($res[self::WAIT_COUNT], $res[self::WAIT_SUM]),
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

        $resMs = $qb
            ->group()->field('id')
            ->subtract(
                $qb->expr()->subtract('$fields.created', DateTimeUtils::getUtcDateTimeFromTimeStamp()),
                $qb->expr()->mod(
                    $qb->expr()->subtract('$fields.created', DateTimeUtils::getUtcDateTimeFromTimeStamp()),
                    $ret * 1_000,
                ),
            )
            ->field('count')->sum(1)
            ->execute()
            ->toArray();

        $res = [];
        foreach ($resMs as $row) {
            $res[$row['_id'] / 1_000] = $row['count'];
        }

        $from = $dateTimeFrom->getTimestamp();
        $to   = $dateTimeTo->getTimestamp();

        $from -= $from % $ret;
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
                    ->gte(DateTimeUtils::getUtcDateTime($dateFrom)),
                $qb->matchExpr()
                    ->field('fields.created')
                    ->lt(DateTimeUtils::getUtcDateTime($dateTo)),
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
        return match ($document) {
            BridgesMetrics::class => Tags::BRIDGE_TAGS,
            RabbitMetrics::class => Tags::RABBIT_TAGS,
            ConnectorsMetrics::class => Tags::CONNECTOR_TAGS,
            MonolithMetrics::class => Tags::MONOLITH_TAGS,
            default => [],
        };
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
