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
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Metrics\Client\ClientInterface;
use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
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
    public const QUEUE_DEPTH  = 'queue_depth';
    public const WAITING_TIME = 'waiting_time';
    public const PROCESS_TIME = 'process_time';
    public const CPU_TIME     = 'cpu_time';
    public const REQUEST_TIME = 'request_time';
    public const ERROR        = 'error';

    // TAGS
    public const TOPOLOGY = 'topology_id';
    public const NODE     = 'node_id';
    public const QUEUE    = 'queue';

    // METRICS
    public const WAIT_TIME          = 'bridge_job_waiting_duration';
    public const NODE_PROCESS_TIME  = 'bridge_job_worker_duration';
    public const NODE_RESULT_ERROR  = 'bridge_job_result_error';
    public const CPU_KERNEL_TIME    = 'fpm_cpu_kernel_time';
    public const REQUEST_TOTAL_TIME = 'sent_request_total_duration';
    public const MESSAGES           = 'messages';

    // ALIASES - COUNT
    private const PROCESSED_COUNT     = 'top_processed_count';
    private const WAIT_COUNT          = 'wait_count';
    private const CPU_COUNT           = 'cpu_count';
    private const REQUEST_COUNT       = 'request_count';
    private const REQUEST_ERROR_COUNT = 'request_error_count';

    // ALIASES - SUM
    private const PROCESSED_SUM     = 'top_processed_sum';
    private const WAIT_SUM          = 'wait_sum';
    private const CPU_SUM           = 'cpu_sum';
    private const REQUEST_SUM       = 'request_sum';
    private const REQUEST_ERROR_SUM = 'request_error_sum';

    // ALIASES - MIN
    private const PROCESSED_MIN = 'top_processed_min';
    private const WAIT_MIN      = 'wait_min';
    private const CPU_MIN       = 'cpu_min';
    private const REQUEST_MIN   = 'request_min';
    private const QUEUE_MIN     = 'queue_min';

    // ALIASES - MAX
    private const PROCESSED_MAX = 'top_processed_max';
    private const WAIT_MAX      = 'wait_max';
    private const CPU_MAX       = 'cpu_max';
    private const REQUEST_MAX   = 'request_max';
    private const QUEUE_MAX     = 'queue_max';

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
     */
    public function __construct(
        ClientInterface $client,
        DocumentManager $dm,
        string $nodeTable,
        string $fpmTable,
        string $rabbitTable
    )
    {
        $this->client         = $client;
        $this->nodeTable      = $nodeTable;
        $this->fpmTable       = $fpmTable;
        $this->rabbitTable    = $rabbitTable;
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
        $res   = [];
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
        $from = $params['from'] ?? NULL;
        $to   = $params['to'] ?? NULL;

        $select = self::getCountForSelect([
            self::NODE_PROCESS_TIME  => self::PROCESSED_COUNT,
            self::WAIT_TIME          => self::WAIT_COUNT,
            self::CPU_KERNEL_TIME    => self::CPU_COUNT,
            self::REQUEST_TOTAL_TIME => self::REQUEST_COUNT,
            self::NODE_RESULT_ERROR  => self::REQUEST_ERROR_COUNT,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getSumForSelect([
            self::NODE_PROCESS_TIME  => self::PROCESSED_SUM,
            self::WAIT_TIME          => self::WAIT_SUM,
            self::CPU_KERNEL_TIME    => self::CPU_SUM,
            self::REQUEST_TOTAL_TIME => self::REQUEST_SUM,
            self::NODE_RESULT_ERROR  => self::REQUEST_ERROR_SUM,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getMinForSelect([
            self::NODE_PROCESS_TIME  => self::PROCESSED_MIN,
            self::WAIT_TIME          => self::WAIT_MIN,
            self::CPU_KERNEL_TIME    => self::CPU_MIN,
            self::REQUEST_TOTAL_TIME => self::REQUEST_MIN,
            self::MESSAGES           => self::QUEUE_MIN,
        ]);
        $select = self::addStringSeparator($select);
        $select .= self::getMaxForSelect([
            self::NODE_PROCESS_TIME  => self::PROCESSED_MAX,
            self::WAIT_TIME          => self::WAIT_MAX,
            self::CPU_KERNEL_TIME    => self::CPU_MAX,
            self::REQUEST_TOTAL_TIME => self::REQUEST_MAX,
            self::MESSAGES           => self::QUEUE_MAX,
        ]);

        $where = [
            self::NODE  => $node->getId(),
            self::QUEUE => GeneratorUtils::generateQueueName($topology, $node),
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
     * @throws MetricsException
     */
    private function runQuery(string $select, array $where, ?string $from = NULL, ?string $to = NULL): array
    {
        $qb = $this->client->getQueryBuilder()
            ->select($select)
            ->from(sprintf('%s,%s,%s', $this->nodeTable, $this->fpmTable, $this->rabbitTable))
            ->where(self::getConditions($where));

        if ($from && $to) {
            $qb->setTimeRange((new DateTime($from))->getTimestamp(), (new DateTime($to))->getTimestamp());
        }
        $this->logger->info('Metrics was selected.', ['Query' => $qb->getQuery()]);
        try {
            $series = $qb->getResultSet()->getSeries();
        } catch (Throwable $e) {
            $this->logger->info($e->getMessage(), ['Exception' => $e]);
            throw new MetricsException('Unknown error occurred during query.', MetricsException::QUERY_ERROR);
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

        if (isset($result[$this->fpmTable])) {
            $cpu
                ->setMin($result[$this->fpmTable][self::CPU_MIN] ?? '')
                ->setMax($result[$this->fpmTable][self::CPU_MAX] ?? '')
                ->setAvg(
                    $result[$this->fpmTable][self::CPU_COUNT] ?? '',
                    $result[$this->fpmTable][self::CPU_SUM] ?? ''
                );
            $request
                ->setMin($result[$this->fpmTable][self::REQUEST_MIN] ?? '')
                ->setMax($result[$this->fpmTable][self::REQUEST_MAX] ?? '')
                ->setAvg(
                    $result[$this->fpmTable][self::REQUEST_COUNT] ?? '',
                    $result[$this->fpmTable][self::REQUEST_SUM] ?? ''
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
                ->setTotal(
                    $result[$this->nodeTable][self::REQUEST_ERROR_COUNT] ?? '',
                    $result[$this->nodeTable][self::REQUEST_ERROR_SUM] ?? ''
                );
        }
        if (isset($result[$this->rabbitTable])) {
            $queue
                ->setMin($result[$this->rabbitTable][self::QUEUE_MIN] ?? '')
                ->setMax($result[$this->rabbitTable][self::QUEUE_MAX] ?? '');
        }

        return $this->generateOutput($queue, $waiting, $process, $cpu, $request, $error);
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

}