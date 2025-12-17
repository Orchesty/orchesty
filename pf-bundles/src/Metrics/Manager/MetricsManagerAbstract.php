<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\NodeRepository;
use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use Hanaboso\Utils\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class MetricsManagerAbstract
 *
 * @package Hanaboso\PipesFramework\Metrics\Manager
 */
abstract class MetricsManagerAbstract implements LoggerAwareInterface
{

    use LoggerTrait;

    // OUTPUT
    public const string QUEUE_DEPTH          = 'queue_depth';
    public const string WAITING_TIME         = 'waiting_time';
    public const string PROCESS_TIME         = 'process_time';
    public const string CPU_TIME             = 'cpu_time';
    public const string REQUEST_TIME         = 'request_time';
    public const string PROCESS              = 'process';
    public const string COUNTER_PROCESS_TIME = 'counter_process_time';

    // TAGS
    public const string TOPOLOGY    = 'topology_id';
    public const string NODE        = 'node_id';
    public const string QUEUE       = 'queue';
    public const string USER        = 'user_id';
    public const string APPLICATION = 'application_id';
    public const string CORRELATION = 'correlation_id';

    // METRICS
    public const string AVG_MESSAGES = 'avg.message';
    public const string MAX_MESSAGES = 'max.message';

    public const string MAX_WAIT_TIME = 'job_max.waiting';
    public const string MIN_WAIT_TIME = 'job_min.waiting';
    public const string AVG_WAIT_TIME = 'avg_waiting.time';

    public const string MAX_PROCESS_TIME = 'job_max.process';
    public const string MIN_PROCESS_TIME = 'job_min.process';
    public const string AVG_PROCESS_TIME = 'avg_process.time';

    public const string MAX_TIME = 'max.time';
    public const string MIN_TIME = 'min.time';
    public const string AVG_TIME = 'avg.time';

    public const string TOTAL_COUNT  = 'total.count';
    public const string FAILED_COUNT = 'failed.count';

    public const string CPU_KERNEL_MIN = 'cpu_min.kernel';
    public const string CPU_KERNEL_MAX = 'cpu_max.kernel';
    public const string CPU_KERNEL_AVG = 'cpu_kernel.avg';

    public const string USER_COUNT = 'user_id.count';
    public const string APP_COUNT  = 'app_id.count';

    // ALIASES - COUNT
    protected const string PROCESSED_COUNT    = 'top_processed_count';
    protected const string WAIT_COUNT         = 'wait_count';
    protected const string CPU_COUNT          = 'cpu_count';
    protected const string REQUEST_COUNT      = 'request_count';
    protected const string PROCESS_TIME_COUNT = 'process_time_count';
    protected const string QUEUE_COUNT        = 'queue_count';

    // ALIASES - SUM
    protected const string PROCESSED_SUM    = 'top_processed_sum';
    protected const string WAIT_SUM         = 'wait_sum';
    protected const string CPU_SUM          = 'cpu_sum';
    protected const string REQUEST_SUM      = 'request_sum';
    protected const string PROCESS_TIME_SUM = 'process_time_sum';
    protected const string NODE_ERROR_SUM   = 'request_error_sum';
    protected const string NODE_TOTAL_SUM   = 'total_count';
    protected const string QUEUE_SUM        = 'queue_sum';

    // ALIASES - MIN
    protected const string PROCESSED_MIN    = 'top_processed_min';
    protected const string WAIT_MIN         = 'wait_min';
    protected const string CPU_MIN          = 'cpu_min';
    protected const string REQUEST_MIN      = 'request_min';
    protected const string PROCESS_TIME_MIN = 'process_time_min';

    // ALIASES - MAX
    protected const string PROCESSED_MAX    = 'top_processed_max';
    protected const string WAIT_MAX         = 'wait_max';
    protected const string CPU_MAX          = 'cpu_max';
    protected const string REQUEST_MAX      = 'request_max';
    protected const string QUEUE_MAX        = 'queue_max';
    protected const string PROCESS_TIME_MAX = 'process_time_max';

    protected const string COUNT = 'COUNT';
    protected const string SUM   = 'SUM';
    protected const string MIN   = 'MIN';
    protected const string MAX   = 'MAX';

    protected const string COUNT_KEY = 'count';
    protected const string SUM_KEY   = 'sum';
    protected const string MIN_KEY   = 'min';
    protected const string AVG_KEY   = 'avg';
    protected const string MAX_KEY   = 'max';

    protected const string CPU_KEY          = 'cpu_%s';
    protected const string REQUEST_KEY      = 'request_%s';
    protected const string WAIT_KEY         = 'wait_%s';
    protected const string PROCESSED_KEY    = 'top_processed_%s';
    protected const string QUEUE_KEY        = 'queue_%s';
    protected const string PROCESS_TIME_KEY = 'process_time_%s';

    /**
     * @var ObjectRepository<Node>&NodeRepository
     */
    protected NodeRepository $nodeRepository;

    /**
     * @param Node     $node
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     */
    abstract public function getNodeMetrics(Node $node, Topology $topology, array $params): array;

    /**
     * @return mixed[]
     */
    abstract public function getHealthcheckMetrics(): array;

    /**
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     */
    abstract public function getTopologyProcessTimeMetrics(Topology $topology, array $params): array;

    /**
     * @param mixed[] $params
     *
     * @return mixed[]
     */
    abstract public function getTopologiesProcessTimeMetrics(array $params): array;

    /**
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     */
    abstract public function getTopologyRequestCountMetrics(Topology $topology, array $params): array;

    /**
     * @param mixed[]     $params
     * @param string|null $key
     *
     * @return mixed[]
     */
    abstract public function getApplicationMetrics(array $params, ?string $key): array;

    /**
     * @param mixed[]     $params
     * @param string|null $user
     *
     * @return mixed[]
     */
    abstract public function getUserMetrics(array $params, ?string $user): array;

    /**
     * MetricsManagerAbstract constructor.
     *
     * @param DocumentManager $dm
     * @param string          $nodeTable
     * @param string          $fpmTable
     * @param string          $rabbitTable
     * @param string          $counterTable
     * @param string          $connectorTable
     * @param string          $consumerTable
     */
    public function __construct(
        DocumentManager $dm,
        protected string $nodeTable,
        protected string $fpmTable,
        protected string $rabbitTable,
        protected string $counterTable,
        protected string $connectorTable,
        protected string $consumerTable,
    )
    {
        $this->nodeRepository = $dm->getRepository(Node::class);
        $this->logger         = new NullLogger();
    }

    /**
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     */
    public function getTopologyMetrics(Topology $topology, array $params): array
    {
        $data = $this->getTopologyProcessTimeMetrics($topology, $params)['process'];
        $res  = [];

        $res['topology'][self::PROCESS_TIME] = [
            self::AVG_KEY => $data[self::AVG_KEY],
            self::MAX_KEY => $data[self::MAX_KEY],
            self::MIN_KEY => $data[self::MIN_KEY],
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
     * @param MetricsDto        $queue
     * @param MetricsDto        $waiting
     * @param MetricsDto        $process
     * @param MetricsDto        $cpu
     * @param MetricsDto | NULL $request
     * @param MetricsDto        $error
     * @param MetricsDto        $counter
     *
     * @return mixed[]
     */
    protected function generateOutput(
        MetricsDto $queue,
        MetricsDto $waiting,
        MetricsDto $process,
        MetricsDto $cpu,
        MetricsDto|null $request,
        MetricsDto $error,
        MetricsDto $counter,
    ): array
    {
        $metrics = [
            self::CPU_TIME     => [
                self::AVG_KEY => $cpu->getAvg(),
                self::MAX_KEY => $cpu->getMax(),
                self::MIN_KEY => $cpu->getMin(),
            ],
            self::PROCESS      => [
                'errors'      => $error->getErrors(),
                'total'       => $error->getTotal(),
                self::AVG_KEY => $counter->getAvg(),
                self::MAX_KEY => $counter->getMax(),
                self::MIN_KEY => $counter->getMin(),
            ],
            self::PROCESS_TIME => [
                self::AVG_KEY => $process->getAvg(),
                self::MAX_KEY => $process->getMax(),
                self::MIN_KEY => $process->getMin(),
            ],
            self::QUEUE_DEPTH  => [
                self::AVG_KEY => $queue->getAvg(),
                self::MAX_KEY => $queue->getMax(),
            ],
            self::WAITING_TIME => [
                self::AVG_KEY => $waiting->getAvg(),
                self::MAX_KEY => $waiting->getMax(),
                self::MIN_KEY => $waiting->getMin(),
            ],
        ];

        if ($request) {
            $metrics[self::REQUEST_TIME] = [
                self::AVG_KEY => $request->getAvg() == 0 ? 'n/a' : $request->getAvg(),
                self::MAX_KEY => $request->getMax(),
                self::MIN_KEY => $request->getMin(),
            ];
        }

        return $metrics;
    }

}
