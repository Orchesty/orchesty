<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics;

use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
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

    private const COUNT = 'COUNT';
    private const SUM   = 'SUM';
    private const MIN   = 'MIN';
    private const MAX   = 'MAX';

    private const COUNT_KEY = 'count';
    private const SUM_KEY   = 'sum';
    private const MIN_KEY   = 'min';
    private const AVG_KEY   = 'avg';
    private const MAX_KEY   = 'max';

    private const CPU_KEY          = 'cpu_%s';
    private const REQUEST_KEY      = 'request_%s';
    private const WAIT_KEY         = 'wait_%s';
    private const PROCESSED_KEY    = 'top_processed_%s';
    private const QUEUE_KEY        = 'queue_%s';
    private const PROCESS_TIME_KEY = 'process_time_%s';

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
     * @throws DateTimeException
     */
    public function getTopologyMetrics(Topology $topology, array $params): array
    {
        $data                                = $this->getTopologyProcessTimeMetrics($topology, $params)['process'];
        $res                                 = [];
        $res['topology'][self::PROCESS_TIME] = [
            self::MIN_KEY => $data[self::MIN_KEY],
            self::AVG_KEY => $data[self::AVG_KEY],
            self::MAX_KEY => $data[self::MAX_KEY],
        ];
        unset($data[self::MIN_KEY], $data[self::AVG_KEY], $data[self::MAX_KEY]);
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
     * @throws DateTimeException
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

        $select = self::addStringSeparator(self::getFunctionForSelect([
            self::AVG_PROCESS_TIME => self::PROCESSED_COUNT,
            self::AVG_WAIT_TIME    => self::WAIT_COUNT,
            self::CPU_KERNEL_AVG   => self::CPU_COUNT,
            self::AVG_TIME         => self::REQUEST_COUNT,
            self::AVG_MESSAGES     => self::QUEUE_COUNT,
        ], self::COUNT));

        $select .= self::addStringSeparator(self::getFunctionForSelect([
            self::AVG_PROCESS_TIME => self::PROCESSED_SUM,
            self::AVG_WAIT_TIME    => self::WAIT_SUM,
            self::CPU_KERNEL_AVG   => self::CPU_SUM,
            self::AVG_TIME         => self::REQUEST_SUM,
            self::FAILED_COUNT     => self::NODE_ERROR_SUM,
            self::TOTAL_COUNT      => self::NODE_TOTAL_SUM,
            self::AVG_MESSAGES     => self::QUEUE_SUM,
        ], self::SUM));

        $select .= self::addStringSeparator(self::getFunctionForSelect([
            self::MIN_PROCESS_TIME => self::PROCESSED_MIN,
            self::MIN_WAIT_TIME    => self::WAIT_MIN,
            self::CPU_KERNEL_MIN   => self::CPU_MIN,
            self::MIN_TIME         => self::REQUEST_MIN,
        ], self::MIN));

        $select .= self::getFunctionForSelect([
            self::MAX_PROCESS_TIME => self::PROCESSED_MAX,
            self::MAX_WAIT_TIME    => self::WAIT_MAX,
            self::CPU_KERNEL_MAX   => self::CPU_MAX,
            self::MAX_TIME         => self::REQUEST_MAX,
            self::MAX_MESSAGES     => self::QUEUE_MAX,
        ], self::MAX);

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
     * @throws DateTimeException
     */
    public function getTopologyProcessTimeMetrics(Topology $topology, array $params): array
    {
        $dateFrom = $params['from'] ?? NULL;
        $dateTo   = $params['to'] ?? NULL;
        $from     = $this->counterTable;

        $select = self::getFunctionForSelect([self::AVG_TIME => self::PROCESS_TIME_COUNT], self::COUNT);
        $select = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::AVG_TIME => self::PROCESS_TIME_SUM], self::SUM);
        $select = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::MIN_TIME => self::PROCESS_TIME_MIN], self::MIN);
        $select = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::MAX_TIME => self::PROCESS_TIME_MAX], self::MAX);
        $select = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::TOTAL_COUNT => self::NODE_TOTAL_SUM], self::SUM);
        $select = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::FAILED_COUNT => self::NODE_ERROR_SUM], self::SUM);

        $where = [self::TOPOLOGY => $topology->getId()];

        return $this->runQuery($select, $from, $where, NULL, $dateFrom, $dateTo);
    }

    /**
     * @param Topology $topology
     * @param array    $params
     *
     * @return array
     * @throws MetricsException
     * @throws DateTimeException
     */
    public function getTopologyRequestCountMetrics(Topology $topology, array $params): array
    {
        $data = $this->getTopologyMetrics($topology, $params);

        $dateFrom = $params['from'] ?? 'now - 1h';
        $dateTo   = $params['to'] ?? 'now';
        $groupBy  = sprintf(
            'TIME(%s)',
            RetentionFactory::getRetention(
                DateTimeUtils::getUTCDateTime($dateFrom),
                DateTimeUtils::getUTCDateTime($dateTo)
            )
        );

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
     * @throws DateTimeException
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
            $dateFrom = DateTimeUtils::getUTCDateTime($dateFrom);
            $dateTo   = DateTimeUtils::getUTCDateTime($dateTo);
            $qb
                ->setTimeRange($dateFrom->getTimestamp(), $dateTo->getTimestamp())
                ->from($this->addRetentionPolicy($from, $dateFrom, $dateTo));
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
     * @param array $serie
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
     * @param MetricsDto $dto
     * @param array      $result
     * @param string     $type
     *
     * @return void
     */
    private function processInnerResult(MetricsDto $dto, array $result, string $type): void
    {
        $dto
            ->setMin($result[sprintf($type, self::MIN_KEY)] ?? '')
            ->setAvg($result[sprintf($type, self::COUNT_KEY)] ?? '', $result[sprintf($type, self::SUM_KEY)] ?? '')
            ->setMax($result[sprintf($type, self::MAX_KEY)] ?? '');
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
            $this->processInnerResult($cpu, $result[$this->fpmTable], self::CPU_KEY);
        }

        if (isset($result[$this->connectorTable])) {
            $this->processInnerResult($request, $result[$this->connectorTable], self::REQUEST_KEY);
        }

        if (isset($result[$this->nodeTable])) {
            $this->processInnerResult($waiting, $result[$this->nodeTable], self::WAIT_KEY);
            $this->processInnerResult($process, $result[$this->nodeTable], self::PROCESSED_KEY);
            $error
                ->setTotal($result[$this->nodeTable][self::NODE_TOTAL_SUM] ?? '')
                ->setErrors($result[$this->nodeTable][self::NODE_ERROR_SUM] ?? '');
        }

        if (isset($result[$this->rabbitTable])) {
            $this->processInnerResult($queue, $result[$this->rabbitTable], self::QUEUE_KEY);
        }

        if (isset($result[$this->counterTable])) {
            $this->processInnerResult($process, $result[$this->counterTable], self::PROCESS_TIME_KEY);
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
     * @throws DateTimeException
     */
    private function processGraphResult(array $series): array
    {
        $data = [];

        if (isset($series[0]['values'])) {
            for ($i = 0; $i < count($series[0]['values']) - 4; $i++) {
                $item                                                          = $series[0]['values'][$i];
                $data[DateTimeUtils::getUTCDateTime($item[0])->getTimestamp()] = $item[1] ?? 0;
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
                self::MAX_KEY => $queue->getMax(),
                self::AVG_KEY => $queue->getAvg(),
            ],
            self::WAITING_TIME => [
                self::MAX_KEY => $waiting->getMax(),
                self::AVG_KEY => $waiting->getAvg(),
                self::MIN_KEY => $waiting->getMin(),
            ],
            self::PROCESS_TIME => [
                self::MAX_KEY => $process->getMax(),
                self::AVG_KEY => $process->getAvg(),
                self::MIN_KEY => $process->getMin(),
            ],
            self::CPU_TIME     => [
                self::MAX_KEY => $cpu->getMax(),
                self::AVG_KEY => $cpu->getAvg(),
                self::MIN_KEY => $cpu->getMin(),
            ],
            self::REQUEST_TIME => [
                self::MAX_KEY => $request->getMax(),
                self::AVG_KEY => $request->getAvg() == 0 ? 'n/a' : $request->getAvg(),
                self::MIN_KEY => $request->getMin(),
            ],
            self::PROCESS      => [
                self::MAX_KEY => $counter->getMax(),
                self::MIN_KEY => $counter->getMin(),
                self::AVG_KEY => $counter->getAvg(),
                'total'       => $error->getTotal(),
                'errors'      => $error->getErrors(),
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
        array_walk($data, function (string &$value, string $key): void {
            $value = sprintf('%s = \'%s\'', $key, $value);
        });

        return [implode(sprintf(' %s ', $delimiter), $data)];
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
     * @param array  $data
     * @param string $function
     *
     * @return string
     */
    private static function getFunctionForSelect(array $data, string $function): string
    {
        return self::createQuery($data, $function);
    }

    /**
     * @param array  $data
     * @param string $function
     *
     * @return string
     */
    private static function createQuery(array $data, string $function): string
    {
        array_walk($data, function (string &$value, string $key) use ($function): void {
            $value = sprintf('%s("%s") AS %s', $function, $key, $value);
        });

        return implode(', ', $data);
    }

    /**
     * @param string   $fromTables
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return string
     */
    private function addRetentionPolicy(string $fromTables, DateTime $from, DateTime $to): string
    {
        $retention = RetentionFactory::getRetention($from, $to);

        return implode(', ', array_map(function (string $item) use ($retention): string {
            return sprintf('"%s".%s', $retention, $item);
        }, explode(',', $fromTables)));
    }

}
