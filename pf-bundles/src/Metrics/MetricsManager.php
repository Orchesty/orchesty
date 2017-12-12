<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 13:36
 */

namespace Hanaboso\PipesFramework\Metrics;

use DateTime;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Client\ClientInterface;
use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
use InfluxDB\Query\Builder;

/**
 * Class MetricsManager
 *
 * @package Hanaboso\PipesFramework\Metrics
 */
class MetricsManager
{

    // OUTPUT
    public const QUEUE_DEPTH  = 'queue_depth';
    public const WAITING_TIME = 'waiting_time';
    public const PROCESS_TIME = 'process_time';
    public const CPU_TIME     = 'cpu_time';
    public const REQUEST_TIME = 'request_time';
    public const ERROR        = 'error';

    // TAGS
    public const TOPOLOGY = 'host';
    public const NODE     = 'name';

    // METRICS
    public const WAIT_TIME          = 'bridge_job_waiting_duration';
    public const NODE_PROCESS_TIME  = 'bridge_job_worker_duration';
    public const TOP_PROCESS_TIME   = 'counter_process_duration';
    public const CPU_KERNEL_TIME    = 'fpm_cpu_kernel_time';
    public const REQUEST_TOTAL_TIME = 'fpm_request_total_duration';

    // ALIASES - COUNT
    private const PROCESSED_COUNT = 'top_processed_count';
    private const WAIT_COUNT      = 'wait_count';
    private const CPU_COUNT       = 'cpu_count';
    private const REQUEST_COUNT   = 'request_count';

    // ALIASES - SUM
    private const PROCESSED_SUM = 'top_processed_sum';
    private const WAIT_SUM      = 'wait_sum';
    private const CPU_SUM       = 'cpu_sum';
    private const REQUEST_SUM   = 'request_sum';

    // ALIASES - MIN
    private const PROCESSED_MIN = 'top_processed_min';
    private const WAIT_MIN      = 'wait_min';
    private const CPU_MIN       = 'cpu_min';
    private const REQUEST_MIN   = 'request_min';

    // ALIASES - MAX
    private const PROCESSED_MAX = 'top_processed_max';
    private const WAIT_MAX      = 'wait_max';
    private const CPU_MAX       = 'cpu_max';
    private const REQUEST_MAX   = 'request_max';

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var string
     */
    private $tableName;

    /**
     * MetricsManager constructor.
     *
     * @param ClientInterface $client
     * @param string          $tableName
     */
    public function __construct(ClientInterface $client, string $tableName)
    {
        $this->builder   = $client->getQueryBuilder();
        $this->tableName = $tableName;
    }

    /**
     * @param Topology $topology
     * @param array    $params
     *
     * @return array
     */
    public function getTopologyMetrics(Topology $topology, array $params): array
    {
        $from = $params['from'] ?? NULL;
        $to   = $params['to'] ?? NULL;

        $select = self::getCountForSelect([
            self::TOP_PROCESS_TIME   => self::PROCESSED_COUNT,
            self::WAIT_TIME          => self::WAIT_COUNT,
            self::CPU_KERNEL_TIME    => self::CPU_COUNT,
            self::REQUEST_TOTAL_TIME => self::REQUEST_COUNT,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getSumForSelect([
            self::TOP_PROCESS_TIME   => self::PROCESSED_SUM,
            self::WAIT_TIME          => self::WAIT_SUM,
            self::CPU_KERNEL_TIME    => self::CPU_SUM,
            self::REQUEST_TOTAL_TIME => self::REQUEST_SUM,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getMinForSelect([
            self::TOP_PROCESS_TIME   => self::PROCESSED_MIN,
            self::WAIT_TIME          => self::WAIT_MIN,
            self::CPU_KERNEL_TIME    => self::CPU_MIN,
            self::REQUEST_TOTAL_TIME => self::REQUEST_MIN,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getMaxForSelect([
            self::TOP_PROCESS_TIME   => self::PROCESSED_MAX,
            self::WAIT_TIME          => self::WAIT_MAX,
            self::CPU_KERNEL_TIME    => self::CPU_MAX,
            self::REQUEST_TOTAL_TIME => self::REQUEST_MAX,
        ]);

        $where = [
            self::TOPOLOGY => GeneratorUtils::createNormalizedServiceName($topology->getId(), $topology->getName()),
        ];

        return $this->runQuery($select, $where, $from, $to);
    }

    /**
     * @param Node  $node
     * @param array $params
     *
     * @return array
     */
    public function getNodeMetrics(Node $node, array $params): array
    {
        $from = $params['from'] ?? NULL;
        $to   = $params['to'] ?? NULL;

        $select = self::getCountForSelect([
            self::TOP_PROCESS_TIME   => self::PROCESSED_COUNT,
            self::WAIT_TIME          => self::WAIT_COUNT,
            self::CPU_KERNEL_TIME    => self::CPU_COUNT,
            self::REQUEST_TOTAL_TIME => self::REQUEST_COUNT,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getSumForSelect([
            self::TOP_PROCESS_TIME   => self::PROCESSED_SUM,
            self::WAIT_TIME          => self::WAIT_SUM,
            self::CPU_KERNEL_TIME    => self::CPU_SUM,
            self::REQUEST_TOTAL_TIME => self::REQUEST_SUM,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getMinForSelect([
            self::TOP_PROCESS_TIME   => self::PROCESSED_MIN,
            self::WAIT_TIME          => self::WAIT_MIN,
            self::CPU_KERNEL_TIME    => self::CPU_MIN,
            self::REQUEST_TOTAL_TIME => self::REQUEST_MIN,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getMaxForSelect([
            self::TOP_PROCESS_TIME   => self::PROCESSED_MAX,
            self::WAIT_TIME          => self::WAIT_MAX,
            self::CPU_KERNEL_TIME    => self::CPU_MAX,
            self::REQUEST_TOTAL_TIME => self::REQUEST_MAX,
        ]);

        $where = [
            self::NODE => GeneratorUtils::createNormalizedServiceName($node->getId(), $node->getName()),
        ];

        return $this->runQuery($select, $where, $from, $to);
    }

    /**
     * -------------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param string      $select
     * @param array       $where
     * @param string|NULL $from
     * @param string|NULL $to
     *
     * @return array
     */
    private function runQuery(string $select, array $where, string $from = NULL, string $to = NULL): array
    {
        $qb = $this->builder
            ->select($select)
            ->from($this->tableName)
            ->where(self::getConditions($where));

        if ($from && $to) {
            $qb->setTimeRange((new DateTime($from))->getTimestamp(), (new DateTime($to))->getTimestamp());
        }

        $result  = $qb->getResultSet()->getPoints();
        $result  = reset($result);
        $waiting = new MetricsDto();
        $waiting
            ->setMin($result[self::WAIT_MIN] ?? '')
            ->setMax($result[self::WAIT_MAX] ?? '')
            ->setAvg($result[self::WAIT_COUNT] ?? '', $result[self::WAIT_SUM] ?? '');
        $process = new MetricsDto();
        $process
            ->setMin($result[self::PROCESSED_MIN] ?? '')
            ->setMax($result[self::PROCESSED_MAX] ?? '')
            ->setAvg($result[self::PROCESSED_COUNT] ?? '', $result[self::PROCESSED_SUM] ?? '');
        $cpu = new MetricsDto();
        $cpu
            ->setMin($result[self::CPU_MIN] ?? '')
            ->setMax($result[self::CPU_MAX] ?? '')
            ->setAvg($result[self::CPU_COUNT] ?? '', $result[self::CPU_SUM] ?? '');
        $request = new MetricsDto();
        $request
            ->setMin($result[self::REQUEST_MIN] ?? '')
            ->setMax($result[self::REQUEST_MAX] ?? '')
            ->setAvg($result[self::REQUEST_COUNT] ?? '', $result[self::REQUEST_SUM] ?? '');

        return $this->generateOutput(new MetricsDto(), $waiting, $process, $cpu, $request, new MetricsDto());
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private static function getConditions(array $data): array
    {
        $ret = [];
        foreach ($data as $name => $value) {
            $ret[] = sprintf('%s = \'%s\'', $name, $value);
        }

        return $ret;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private static function addStringSeparator(string $string): string
    {
        return sprintf('%s, ', $string);
    }

    /**
     * @param array $counts
     *
     * @return string
     */
    private static function getCountForSelect(array $counts): string
    {
        return self::createQuery($counts, 'COUNT');
    }

    /**
     * @param array $sums
     *
     * @return string
     */
    private static function getSumForSelect(array $sums): string
    {
        return self::createQuery($sums, 'SUM');
    }

    /**
     * @param array $sums
     *
     * @return string
     */
    private static function getMinForSelect(array $sums): string
    {
        return self::createQuery($sums, 'MIN');
    }

    /**
     * @param array $sums
     *
     * @return string
     */
    private static function getMaxForSelect(array $sums): string
    {
        return self::createQuery($sums, 'MAX');
    }

    /**
     * @param array  $data
     * @param string $funcName
     *
     * @return string
     */
    private static function createQuery(array $data, string $funcName): string
    {
        $ret   = '';
        $first = TRUE;
        foreach ($data as $key => $alias) {
            if (!$first) {
                $ret .= sprintf(', %s("%s") as %s', $funcName, $key, $alias);
                continue;
            }

            $first = FALSE;
            $ret   = sprintf('%s("%s") as %s', $funcName, $key, $alias);
        }

        return $ret;
    }

    /**
     * @param MetricsDto $queue
     * @param MetricsDto $waiting
     * @param MetricsDto $process
     * @param MetricsDto $cpu
     * @param MetricsDto $request
     * @param MetricsDto $error
     *
     * @return array
     */
    private function generateOutput(
        MetricsDto $queue,
        MetricsDto $waiting,
        MetricsDto $process,
        MetricsDto $cpu,
        MetricsDto $request,
        MetricsDto $error
    ): array
    {
        $output = [
            self::QUEUE_DEPTH  => [
                'max' => $queue->getMax(), 'min' => $queue->getMin(),
            ],
            self::WAITING_TIME => [
                'max' => $waiting->getMax(), 'min' => $waiting->getMin(), 'avg' => $waiting->getAvg(),
            ],
            self::PROCESS_TIME => [
                'max' => $process->getMax(), 'min' => $process->getMin(), 'avg' => $process->getAvg(),
            ],
            self::CPU_TIME     => [
                'max' => $cpu->getMax(), 'min' => $cpu->getMin(), 'avg' => $cpu->getAvg(),
            ],
            self::REQUEST_TIME => [
                'max' => $request->getMax(), 'min' => $request->getMin(), 'avg' => $request->getAvg(),
            ],
            self::ERROR        => [
                'total' => $error->getTotal(),
            ],
        ];

        return $output;
    }

}