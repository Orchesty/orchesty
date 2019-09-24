<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Manager;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Database\Document\Node;
use Hanaboso\CommonsBundle\Database\Document\Topology;
use Hanaboso\CommonsBundle\Database\Repository\NodeRepository;
use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class MetricsManagerAbstract
 *
 * @package Hanaboso\PipesFramework\Metrics\Manager
 */
abstract class MetricsManagerAbstract implements LoggerAwareInterface
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
    protected const PROCESSED_COUNT    = 'top_processed_count';
    protected const WAIT_COUNT         = 'wait_count';
    protected const CPU_COUNT          = 'cpu_count';
    protected const REQUEST_COUNT      = 'request_count';
    protected const PROCESS_TIME_COUNT = 'process_time_count';
    protected const QUEUE_COUNT        = 'queue_count';

    // ALIASES - SUM
    protected const PROCESSED_SUM    = 'top_processed_sum';
    protected const WAIT_SUM         = 'wait_sum';
    protected const CPU_SUM          = 'cpu_sum';
    protected const REQUEST_SUM      = 'request_sum';
    protected const PROCESS_TIME_SUM = 'process_time_sum';
    protected const NODE_ERROR_SUM   = 'request_error_sum';
    protected const NODE_TOTAL_SUM   = 'total_count';
    protected const QUEUE_SUM        = 'queue_sum';

    // ALIASES - MIN
    protected const PROCESSED_MIN    = 'top_processed_min';
    protected const WAIT_MIN         = 'wait_min';
    protected const CPU_MIN          = 'cpu_min';
    protected const REQUEST_MIN      = 'request_min';
    protected const PROCESS_TIME_MIN = 'process_time_min';

    // ALIASES - MAX
    protected const PROCESSED_MAX    = 'top_processed_max';
    protected const WAIT_MAX         = 'wait_max';
    protected const CPU_MAX          = 'cpu_max';
    protected const REQUEST_MAX      = 'request_max';
    protected const QUEUE_MAX        = 'queue_max';
    protected const PROCESS_TIME_MAX = 'process_time_max';

    protected const COUNT = 'COUNT';
    protected const SUM   = 'SUM';
    protected const MIN   = 'MIN';
    protected const MAX   = 'MAX';

    protected const COUNT_KEY = 'count';
    protected const SUM_KEY   = 'sum';
    protected const MIN_KEY   = 'min';
    protected const AVG_KEY   = 'avg';
    protected const MAX_KEY   = 'max';

    protected const CPU_KEY          = 'cpu_%s';
    protected const REQUEST_KEY      = 'request_%s';
    protected const WAIT_KEY         = 'wait_%s';
    protected const PROCESSED_KEY    = 'top_processed_%s';
    protected const QUEUE_KEY        = 'queue_%s';
    protected const PROCESS_TIME_KEY = 'process_time_%s';

    /**
     * @var NodeRepository|ObjectRepository
     */
    protected $nodeRepository;

    /**
     * @var string
     */
    protected $nodeTable;

    /**
     * @var string
     */
    protected $fpmTable;

    /**
     * @var string
     */
    protected $rabbitTable;

    /**
     * @var string
     */
    protected $counterTable;

    /**
     * @var string
     */
    protected $connectorTable;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * MetricsManager constructor.
     *
     * @param DocumentManager $dm
     * @param string          $nodeTable
     * @param string          $fpmTable
     * @param string          $rabbitTable
     * @param string          $counterTable
     * @param string          $connectorTable
     */
    public function __construct(
        DocumentManager $dm,
        string $nodeTable,
        string $fpmTable,
        string $rabbitTable,
        string $counterTable,
        string $connectorTable
    )
    {
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
     * @return MetricsManagerAbstract
     */
    public function setLogger(LoggerInterface $logger): MetricsManagerAbstract
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param Topology $topology
     * @param array    $params
     *
     * @return array
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
     */
    public abstract function getNodeMetrics(Node $node, Topology $topology, array $params): array;

    /**
     * @param Topology $topology
     * @param array    $params
     *
     * @return array
     */
    public abstract function getTopologyProcessTimeMetrics(Topology $topology, array $params): array;

    /**
     * @param Topology $topology
     * @param array    $params
     *
     * @return array
     */
    public abstract function getTopologyRequestCountMetrics(Topology $topology, array $params): array;

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
    protected function generateOutput(
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

}
