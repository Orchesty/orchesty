<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 13:36
 */

namespace Hanaboso\PipesFramework\Metrics;

use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Metrics\Client\ClientInterface;
use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\Retention\RetentionFactory;
use Hanaboso\PipesFramework\Utils\GeneratorUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Class MetricsManager
 *
 * @package Hanaboso\PipesFramework\Metrics
 */
class MetricsManager implements LoggerAwareInterface
{

    // OUTPUT
    public const QUEUE_DEPTH          = 'queue_depth';
    public const WAITING_TIME         = 'waiting_time';
    public const PROCESS_TIME         = 'process_time';
    public const CPU_TIME             = 'cpu_time';
    public const REQUEST_TIME         = 'request_time';
    public const PROCESS              = 'process';
    public const COUNTER_PROCESS_TIME = 'counter_process_time';

    // TAGS
    public const TOPOLOGY = 'topology_id';
    public const NODE     = 'node_id';
    public const QUEUE    = 'queue';

    // METRICS
    public const AVG_MESSAGES = 'avg.message';
    public const MAX_MESSAGES = 'max.message';

    public const MAX_WAIT_TIME = 'job_max.waiting';
    public const MIN_WAIT_TIME = 'job_min.waiting';
    public const AVG_WAIT_TIME = 'avg_waiting.time';

    public const MAX_PROCESS_TIME = 'job_max.process';
    public const MIN_PROCESS_TIME = 'job_min.process';
    public const AVG_PROCESS_TIME = 'avg_process.time';

    public const MAX_TIME = 'max.time';
    public const MIN_TIME = 'min.time';
    public const AVG_TIME = 'avg.time';

    public const TOTAL_COUNT  = 'total.count';
    public const FAILED_COUNT = 'failed.count';

    public const CPU_KERNEL_MIN = 'cpu_min.kernel';
    public const CPU_KERNEL_MAX = 'cpu_max.kernel';
    public const CPU_KERNEL_AVG = 'cpu_kernel.avg';

    // ALIASES - COUNT
    private const PROCESSED_COUNT    = 'top_processed_count';
    private const WAIT_COUNT         = 'wait_count';
    private const CPU_COUNT          = 'cpu_count';
    private const REQUEST_COUNT      = 'request_count';
    private const PROCESS_TIME_COUNT = 'process_time_count';
    private const QUEUE_COUNT        = 'queue_count';

    // ALIASES - SUM
    private const PROCESSED_SUM    = 'top_processed_sum';
    private const WAIT_SUM         = 'wait_sum';
    private const CPU_SUM          = 'cpu_sum';
    private const REQUEST_SUM      = 'request_sum';
    private const PROCESS_TIME_SUM = 'process_time_sum';
    private const NODE_ERROR_SUM   = 'request_error_sum';
    private const NODE_TOTAL_SUM   = 'total_count';
    private const QUEUE_SUM        = 'QUEUE_SUM';

    // ALIASES - MIN
    private const PROCESSED_MIN    = 'top_processed_min';
    private const WAIT_MIN         = 'wait_min';
    private const CPU_MIN          = 'cpu_min';
    private const REQUEST_MIN      = 'request_min';
    private const PROCESS_TIME_MIN = 'process_time_min';

    // ALIASES - MAX
    private const PROCESSED_MAX    = 'top_processed_max';
    private const WAIT_MAX         = 'wait_max';
    private const CPU_MAX          = 'cpu_max';
    private const REQUEST_MAX      = 'request_max';
    private const QUEUE_MAX        = 'queue_max';
    private const PROCESS_TIME_MAX = 'process_time_max';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var NodeRepository|ObjectRepository
     */
    private $nodeRepository;

    /**
     * @var string
     */
    private $nodeTable;

    /**
     * @var string
     */
    private $fpmTable;

    /**
     * @var string
     */
    private $rabbitTable;

    /**
     * @var string
     */
    private $counterTable;

    /**
     * @var string
     */
    private $connectorTable;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MetricsManager constructor.
     *
     * @param ClientInterface $client
     * @param DocumentManager $dm
     * @param string          $nodeTable
     * @param string          $fpmTable
     * @param string          $rabbitTable
     * @param string          $counterTable
     * @param string          $connectorTable
     */
    public function __construct(
        ClientInterface $client,
        DocumentManager $dm,
        string $nodeTable,
        string $fpmTable,
        string $rabbitTable,
        string $counterTable,
        string $connectorTable
    )
    {
        $this->client         = $client;
        $this->nodeTable      = $nodeTable;
        $this->fpmTable       = $fpmTable;
        $this->rabbitTable    = $rabbitTable;
        $this->counterTable   = $counterTable;
        $this->connectorTable = $connectorTable;
        $this->nodeRepository = $dm->getRepository(Node::class);
        $this->logger         = new NullLogger();
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return MetricsManager
     */
    public function setLogger(LoggerInterface $logger): MetricsManager
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param Topology $topology
     * @param array    $params
     *
     * @return array
     * @throws MetricsException
     */
    public function getTopologyMetrics(Topology $topology, array $params): array
    {
        $data                                = $this->getTopologyProcessTimeMetrics($topology, $params)['process'];
        $res['topology'][self::PROCESS_TIME] = ['min' => $data['min'], 'avg' => $data['avg'], 'max' => $data['max']];
        unset($data['min'], $data['avg'], $data['max']);
        $res['topology']['process'] = $data;

        /** @var Node[] $nodes */
        $nodes = $this->nodeRepository->findBy(['topology' => $topology->getId()]);
        foreach ($nodes as $node) {
            $res[$node->getId()] = $this->getNodeMetrics($node, $topology, $params);
        }

        return $res;
    }

    /**
     * @param Node     $node
     * @param Topology $topology
     * @param array    $params
     *
     * @return array
     * @throws MetricsException
     */
    public function getNodeMetrics(Node $node, Topology $topology, array $params): array
    {
        $dateFrom = $params['from'] ?? NULL;
        $dateTo   = $params['to'] ?? NULL;
        $from     = sprintf(
            '%s,%s,%s,%s',
            $this->nodeTable,
            $this->fpmTable,
            $this->rabbitTable,
            $this->connectorTable
        );

        $select = self::getCountForSelect([
            self::AVG_PROCESS_TIME => self::PROCESSED_COUNT,
            self::AVG_WAIT_TIME    => self::WAIT_COUNT,
            self::CPU_KERNEL_AVG   => self::CPU_COUNT,
            self::AVG_TIME         => self::REQUEST_COUNT,
            self::AVG_MESSAGES     => self::QUEUE_COUNT,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getSumForSelect([
            self::AVG_PROCESS_TIME => self::PROCESSED_SUM,
            self::AVG_WAIT_TIME    => self::WAIT_SUM,
            self::CPU_KERNEL_AVG   => self::CPU_SUM,
            self::AVG_TIME         => self::REQUEST_SUM,
            self::FAILED_COUNT     => self::NODE_ERROR_SUM,
            self::TOTAL_COUNT      => self::NODE_TOTAL_SUM,
            self::AVG_MESSAGES     => self::QUEUE_SUM,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getMinForSelect([
            self::MIN_PROCESS_TIME => self::PROCESSED_MIN,
            self::MIN_WAIT_TIME    => self::WAIT_MIN,
            self::CPU_KERNEL_MIN   => self::CPU_MIN,
            self::MIN_TIME         => self::REQUEST_MIN,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getMaxForSelect([
            self::MAX_PROCESS_TIME => self::PROCESSED_MAX,
            self::MAX_WAIT_TIME    => self::WAIT_MAX,
            self::CPU_KERNEL_MAX   => self::CPU_MAX,
            self::MAX_TIME         => self::REQUEST_MAX,
            self::MAX_MESSAGES     => self::QUEUE_MAX,
        ]);

        $where = [
            self::NODE  => $node->getId(),
            self::QUEUE => GeneratorUtils::generateQueueName($topology, $node),
        ];

        return $this->runQuery($select, $from, $where, NULL, $dateFrom, $dateTo);
    }

    /**
     * @param Topology $topology
     * @param array    $params
     *
     * @return array
     * @throws MetricsException
     */
    public function getTopologyProcessTimeMetrics(Topology $topology, array $params): array
    {
        $dateFrom = $params['from'] ?? NULL;
        $dateTo   = $params['to'] ?? NULL;
        $from     = $this->counterTable;

        $select = self::getCountForSelect([self::AVG_TIME => self::PROCESS_TIME_COUNT]);
        $select = self::addStringSeparator($select);
        $select .= self::getSumForSelect([self::AVG_TIME => self::PROCESS_TIME_SUM]);
        $select = self::addStringSeparator($select);
        $select .= self::getMinForSelect([self::MIN_TIME => self::PROCESS_TIME_MIN]);
        $select = self::addStringSeparator($select);
        $select .= self::getMaxForSelect([self::MAX_TIME => self::PROCESS_TIME_MAX]);
        $select = self::addStringSeparator($select);
        $select .= self::getSumForSelect([self::TOTAL_COUNT => self::NODE_TOTAL_SUM]);
        $select = self::addStringSeparator($select);
        $select .= self::getSumForSelect([self::FAILED_COUNT => self::NODE_ERROR_SUM]);

        $where = [self::TOPOLOGY => $topology->getId()];

        return $this->runQuery($select, $from, $where, NULL, $dateFrom, $dateTo);
    }

    /**
     * @param Topology $topology
     * @param array    $params
     *
     * @return array
     * @throws MetricsException
     * @throws EnumException
     */
    public function getTopologyRequestCountMetrics(Topology $topology, array $params): array
    {
        $data = $this->getTopologyMetrics($topology, $params);

        $dateFrom = $params['from'] ?? 'now - 1h';
        $dateTo   = $params['to'] ?? 'now';
        $groupBy  = sprintf('TIME(%s)', RetentionFactory::getRetention(new DateTime($dateFrom), new DateTime($dateTo)));

        $data['requests'] = $this->runQuery(
            sprintf('SUM(%s) AS count', self::TOTAL_COUNT),
            $this->counterTable,
            [sprintf("%s = '%s'", self::TOPOLOGY, $topology->getId())],
            $groupBy,
            $dateFrom,
            $dateTo,
            TRUE
        );

        return $data;
    }

    /**
     * -------------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param string      $select
     * @param string      $from
     * @param array       $where
     * @param string|NULL $group
     * @param string|NULL $dateFrom
     * @param string|NULL $dateTo
     * @param bool        $forGraph
     *
     * @return array
     * @throws MetricsException
     */
    private function runQuery(
        string $select,
        string $from,
        array $where,
        ?string $group = NULL,
        ?string $dateFrom = NULL,
        ?string $dateTo = NULL,
        bool $forGraph = FALSE
    ): array
    {
        $qb = $this->client->getQueryBuilder()
            ->select($select)
            ->from($from)
            ->where($forGraph ? $where : self::getConditions($where));

        if ($group) {
            $qb->groupBy($group);
        }

        if ($dateFrom && $dateTo) {
            $fromDate = new DateTime($dateFrom);
            $to       = new DateTime($dateTo);
            $qb
                ->setTimeRange($fromDate->getTimestamp(), $to->getTimestamp())
                ->from($this->addRetentionPolicy($from, $fromDate, $to));
        }
        $this->logger->debug('Metrics was selected.', ['Query' => $qb->getQuery()]);
        try {
            $series = $qb->getResultSet()->getSeries();
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['Exception' => $e]);
            throw new MetricsException('Unknown error occurred during query.', MetricsException::QUERY_ERROR);
        }

        if ($forGraph) {
            return $this->processGraphResult($series);
        }

        return $this->processResultSet($this->getPoints($series));
    }

    /**
     * @param array $series
     *
     * @return array $points
     */
    public function getPoints(array $series): array
    {
        $points = [];

        foreach ($series as $serie) {
            if (isset($serie['values']) && isset($serie['name'])) {
                foreach ($this->getPointsFromSerie($serie) as $point) {
                    $points[$serie['name']] = $point;
                }
            }
        }

        return $points;
    }

    /**
     * @param  array $serie
     *
     * @return array
     */
    private function getPointsFromSerie(array $serie): array
    {
        $points = [];

        foreach ($serie['values'] as $point) {
            $points[] = array_combine($serie['columns'], $point);
        }

        return $points;
    }

    /**
     * @param array $result
     *
     * @return array
     */
    private function processResultSet(array $result): array
    {
        $waiting = new MetricsDto();
        $process = new MetricsDto();
        $cpu     = new MetricsDto();
        $request = new MetricsDto();
        $queue   = new MetricsDto();
        $error   = new MetricsDto();
        $counter = new MetricsDto();

        if (isset($result[$this->fpmTable])) {
            $cpu
                ->setMin($result[$this->fpmTable][self::CPU_MIN] ?? '')
                ->setMax($result[$this->fpmTable][self::CPU_MAX] ?? '')
                ->setAvg(
                    $result[$this->fpmTable][self::CPU_COUNT] ?? '',
                    $result[$this->fpmTable][self::CPU_SUM] ?? ''
                );
        }
        if (isset($result[$this->connectorTable])) {
            $request
                ->setMin($result[$this->connectorTable][self::REQUEST_MIN] ?? '')
                ->setMax($result[$this->connectorTable][self::REQUEST_MAX] ?? '')
                ->setAvg(
                    $result[$this->connectorTable][self::REQUEST_COUNT] ?? '',
                    $result[$this->connectorTable][self::REQUEST_SUM] ?? ''
                );
        }
        if (isset($result[$this->nodeTable])) {
            $waiting
                ->setMin($result[$this->nodeTable][self::WAIT_MIN] ?? '')
                ->setMax($result[$this->nodeTable][self::WAIT_MAX] ?? '')
                ->setAvg(
                    $result[$this->nodeTable][self::WAIT_COUNT] ?? '',
                    $result[$this->nodeTable][self::WAIT_SUM] ?? ''
                );
            $process
                ->setMin($result[$this->nodeTable][self::PROCESSED_MIN] ?? '')
                ->setMax($result[$this->nodeTable][self::PROCESSED_MAX] ?? '')
                ->setAvg(
                    $result[$this->nodeTable][self::PROCESSED_COUNT] ?? '',
                    $result[$this->nodeTable][self::PROCESSED_SUM] ?? ''
                );
            $error
                ->setTotal($result[$this->nodeTable][self::NODE_TOTAL_SUM] ?? '')
                ->setErrors($result[$this->nodeTable][self::NODE_ERROR_SUM] ?? '');

        }
        if (isset($result[$this->rabbitTable])) {
            $queue
                ->setMax($result[$this->rabbitTable][self::QUEUE_MAX] ?? '')
                ->setAvg(
                    $result[$this->rabbitTable][self::QUEUE_COUNT] ?? '',
                    $result[$this->rabbitTable][self::QUEUE_SUM] ?? ''
                );
        }
        if (isset($result[$this->counterTable])) {
            $counter
                ->setMin($result[$this->counterTable][self::PROCESS_TIME_MIN] ?? '')
                ->setMax($result[$this->counterTable][self::PROCESS_TIME_MAX] ?? '')
                ->setAvg(
                    $result[$this->counterTable][self::PROCESS_TIME_COUNT] ?? '',
                    $result[$this->counterTable][self::PROCESS_TIME_SUM] ?? ''
                );
            $error
                ->setTotal($result[$this->counterTable][self::NODE_TOTAL_SUM] ?? '')
                ->setErrors($result[$this->counterTable][self::NODE_ERROR_SUM] ?? '');
        }

        return $this->generateOutput($queue, $waiting, $process, $cpu, $request, $error, $counter);
    }

    /**
     * @param array $series
     *
     * @return array
     */
    private function processGraphResult(array $series): array
    {
        $data = [];
        if (isset($series[0]['values'])) {
            $total = count($series[0]['values']);
            $i     = 1;
            foreach ($series[0]['values'] as $item) {
                if ($i > ($total - 4) && empty($item[1])) {
                    break;
                } else {
                    $data[(new DateTime($item[0]))->getTimestamp()] = $item[1] ?? 0;
                }
                $i++;
            }
        }

        return $data;
    }

    /**
     * @param MetricsDto $queue
     * @param MetricsDto $waiting
     * @param MetricsDto $process
     * @param MetricsDto $cpu
     * @param MetricsDto $request
     * @param MetricsDto $error
     * @param MetricsDto $counter
     *
     * @return array
     */
    private function generateOutput(
        MetricsDto $queue,
        MetricsDto $waiting,
        MetricsDto $process,
        MetricsDto $cpu,
        MetricsDto $request,
        MetricsDto $error,
        MetricsDto $counter
    ): array
    {
        return [
            self::QUEUE_DEPTH  => [
                'max' => $queue->getMax(),
                'avg' => $queue->getAvg(),
            ],
            self::WAITING_TIME => [
                'max' => $waiting->getMax(),
                'min' => $waiting->getMin(),
                'avg' => $waiting->getAvg(),
            ],
            self::PROCESS_TIME => [
                'max' => $process->getMax(),
                'min' => $process->getMin(),
                'avg' => $process->getAvg(),
            ],
            self::CPU_TIME     => [
                'max' => $cpu->getMax(),
                'min' => $cpu->getMin(),
                'avg' => $cpu->getAvg(),
            ],
            self::REQUEST_TIME => [
                'max' => $request->getMax(),
                'min' => $request->getMin(),
                'avg' => ($request->getAvg() == 0) ? 'n/a' : $request->getAvg(),
            ],
            self::PROCESS      => [
                'max'    => $counter->getMax(),
                'min'    => $counter->getMin(),
                'avg'    => $counter->getAvg(),
                'total'  => $error->getTotal(),
                'errors' => $error->getErrors(),
            ],
        ];
    }

    /**
     * @param array  $data
     * @param string $delimiter
     *
     * @return array
     */
    private static function getConditions(array $data, string $delimiter = 'or'): array
    {
        $ret   = '';
        $first = TRUE;
        foreach ($data as $name => $value) {
            if (!$first) {
                $ret .= sprintf(' %s ', $delimiter);
            }

            $ret   .= sprintf('%s = \'%s\'', $name, $value);
            $first = FALSE;
        }

        return [$ret];
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
                $ret .= ',';
            }

            $first = FALSE;
            $ret   .= sprintf('%s("%s") as %s', $funcName, $key, $alias);
        }

        return $ret;
    }

    /**
     * @param string   $fromTables
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return string
     */
    private function addRetentionPolicy(
        string $fromTables,
        DateTime $from,
        DateTime $to
    ): string
    {
        $out       = '';
        $retention = RetentionFactory::getRetention($from, $to);
        foreach (explode(',', $fromTables) as $item) {
            if (!empty($out)) {
                $out .= ',';
            }

            $out .= sprintf('"%s".%s', $retention, $item);
        }

        return $out;
    }

}