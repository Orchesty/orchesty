<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Configurator\Document\Limiter;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Configurator\Model\Filters\ProcessAggregationFilter;
use Hanaboso\PipesFramework\Configurator\Model\Filters\ProcessGraphAggregationFilter;
use Hanaboso\PipesFramework\Configurator\Model\Filters\ProcessTotalAggregationFilter;
use Hanaboso\PipesFramework\Configurator\Model\Filters\TopologyAggregationFilter;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\Utils\Date\DateTimeUtils;
use LogicException;

/**
 * Class ProcessManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final readonly class ProcessManager
{

    /**
     * ProcessManager constructor.
     *
     * @param DocumentManager               $dm
     * @param DocumentManager               $metricsDm
     * @param ProcessAggregationFilter      $processAggregationFilter
     * @param ProcessTotalAggregationFilter $processTotalAggregationFilter
     * @param ProcessGraphAggregationFilter $processGraphAggregationFilter
     * @param TopologyAggregationFilter     $topologyAggregationFilter
     */
    public function __construct(
        private DocumentManager $dm,
        private DocumentManager $metricsDm,
        private ProcessAggregationFilter $processAggregationFilter,
        private ProcessTotalAggregationFilter $processTotalAggregationFilter,
        private ProcessGraphAggregationFilter $processGraphAggregationFilter,
        private TopologyAggregationFilter $topologyAggregationFilter,
    )
    {
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getProcesses(GridRequestDtoInterface $dto): array
    {
        return $this->processAggregationFilter->getData($dto)->toArray();
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getProcessesTotal(GridRequestDtoInterface $dto): array
    {
        return $this->processTotalAggregationFilter->getData($dto)->toArray();
    }

    /**
     * @param GridRequestDtoInterface $dto
     * @param int                     $buckets
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getProcessesGraph(GridRequestDtoInterface $dto, int $buckets): array
    {
        $this->processGraphAggregationFilter->setBucketCount($buckets);

        return $this->processGraphAggregationFilter->getData($dto)->toArray();
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getProcessesTopologies(GridRequestDtoInterface $dto): array
    {
        return $this->topologyAggregationFilter->getData($dto)->toArray();
    }

    /**
     * @param string $id
     *
     * @return array<mixed>
     */
    public function getProcessDetail(string $id): array
    {
        /** @var TopologyProgress|null $topologyProgress */
        $topologyProgress = $this->dm->getRepository(TopologyProgress::class)->find($id);

        if (!$topologyProgress) {
            throw new LogicException(sprintf('TopologyProgress "%s" not found.', $id));
        }

        $startedAt    = $topologyProgress->getStartedAt();
        $finishedAt   = $topologyProgress->getFinishedAt();
        $topologyName = '';

        /** @var Topology|null $topology */
        $topology = $this->dm->getRepository(Topology::class)->find($topologyProgress->getTopologyId());

        if ($topology) {
            $topologyName = sprintf('%s v%s', $topology->getName(), $topology->getVersion());
        }

        // Count per node + type on the DB side. The former approach hydrated
        // every matching UserTask / Limiter document (message body included)
        // into PHP on each call — with the process detail polled every ~2s and
        // nodes able to hold thousands of breakpoint messages, that meant
        // repeatedly transferring and hydrating thousands of documents. A
        // grouped aggregation returns just the counts.
        $userTaskBuilder = $this->dm->createAggregationBuilder(UserTask::class);
        $userTaskRows    = $userTaskBuilder
            ->match()
            ->field('correlationId')
            ->equals($id)
            ->group()
            ->field('_id')
            ->expression('$nodeId')
            ->field('nodeName')
            ->first('$nodeName')
            ->field('breakpointCount')
            ->sum($userTaskBuilder->expr()->cond($userTaskBuilder->expr()->eq('$type', 'userTask'), 1, 0))
            ->field('trashCount')
            ->sum($userTaskBuilder->expr()->cond($userTaskBuilder->expr()->eq('$type', 'userTask'), 0, 1))
            ->execute()
            ->toArray();

        $limiterBuilder = $this->dm->createAggregationBuilder(Limiter::class);
        $limiterRows    = $limiterBuilder
            ->match()
            ->field('message.headers.correlation-id')
            ->equals($id)
            ->group()
            ->field('_id')
            ->expression('$message.headers.node-id')
            ->field('nodeName')
            ->first('$message.headers.node-name')
            ->field('repeaterCount')
            ->sum($limiterBuilder->expr()->cond($limiterBuilder->expr()->eq('$prioritize', TRUE), 1, 0))
            ->field('limiterCount')
            ->sum($limiterBuilder->expr()->cond($limiterBuilder->expr()->eq('$prioritize', TRUE), 0, 1))
            ->execute()
            ->toArray();

        $nodes                = [];
        $totalCountTrash      = 0;
        $totalCountLimiter    = 0;
        $totalCountRepeater   = 0;
        $totalCountBreakpoint = 0;

        foreach ($userTaskRows as $row) {
            $nodeId = (string) ($row['_id'] ?? '');

            if (!isset($nodes[$nodeId])) {
                $nodes[$nodeId] = self::emptyNode((string) ($row['nodeName'] ?? ''));
            }

            $breakpoint = (int) ($row['breakpointCount'] ?? 0);
            $trash      = (int) ($row['trashCount'] ?? 0);

            $nodes[$nodeId]['breakpointCount'] += $breakpoint;
            $nodes[$nodeId]['trashCount']      += $trash;
            $totalCountBreakpoint              += $breakpoint;
            $totalCountTrash                   += $trash;
        }

        foreach ($limiterRows as $row) {
            $nodeId = (string) ($row['_id'] ?? '');

            if ($nodeId === '') {
                continue;
            }

            if (!isset($nodes[$nodeId])) {
                $nodes[$nodeId] = self::emptyNode((string) ($row['nodeName'] ?? ''));
            }

            $repeater = (int) ($row['repeaterCount'] ?? 0);
            $limiter  = (int) ($row['limiterCount'] ?? 0);

            $nodes[$nodeId]['repeaterCount'] += $repeater;
            $nodes[$nodeId]['limiterCount']  += $limiter;
            $totalCountRepeater              += $repeater;
            $totalCountLimiter               += $limiter;
        }

        $processTimes = $this->aggregateMetrics(BridgesMetrics::class, $id, '$fields.worker_duration');
        $requestTimes = $this->aggregateMetrics(ConnectorsMetrics::class, $id, '$fields.sent_request_total_duration');

        foreach ($processTimes as $nodeId => $duration) {
            if (!isset($nodes[$nodeId])) {
                $nodes[$nodeId] = self::emptyNode('');
            }

            $nodes[$nodeId]['processTime'] = $duration;
        }

        foreach ($requestTimes as $nodeId => $duration) {
            if (!isset($nodes[$nodeId])) {
                $nodes[$nodeId] = self::emptyNode('');
            }

            $nodes[$nodeId]['requestTime'] = $duration;
        }

        $nokCount = $topologyProgress->getNok();

        return [
            'duration'   => TopologyProgress::durationInMs($startedAt, $finishedAt ?? DateTimeUtils::getUtcDateTime()),
            'finished'   => $finishedAt?->format(DateTimeUtils::DATE_TIME_UTC),
            'id'         => $topologyProgress->getId(),
            'nodes'      => $nodes,
            'nokCount'   => $nokCount,
            'okCount'    => $topologyProgress->getOk(),
            'source'     => $topologyProgress->getSource(),
            'started'    => $startedAt->format(DateTimeUtils::DATE_TIME_UTC),
            'status'     => $topologyProgress->isTerminated() ? 'TERMINATED' : ($finishedAt === NULL ? 'IN PROGRESS' : ($nokCount > 0 ? 'FAILED' : 'COMPLETED')),
            'topology'   => [
                'breakpointCount' => $totalCountBreakpoint,
                'limiterCount'    => $totalCountLimiter,
                'name'            => $topologyName,
                'repeaterCount'   => $totalCountRepeater,
                'trashCount'      => $totalCountTrash,
            ],
            'totalCount' => $topologyProgress->getTotal(),
        ];
    }

    /**
     * @param class-string $documentClass
     * @param string       $correlationId
     * @param string       $durationField
     *
     * @return int[]
     */
    private function aggregateMetrics(string $documentClass, string $correlationId, string $durationField): array
    {
        $builder = $this
            ->metricsDm
            ->createAggregationBuilder($documentClass)
            ->match()
            ->field('tags.correlation_id')
            ->equals($correlationId)
            ->group()
            ->field('_id')
            ->expression('$tags.node_id')
            ->field('duration')
            ->avg($durationField)
            ->project()
            ->field('duration')
            ->round('$duration');

        $result = [];

        foreach ($builder->execute()->toArray() as $row) {
            $result[(string) $row['_id']] = (int) $row['duration'];
        }

        return $result;
    }

    /**
     * @param string $name
     *
     * @return mixed[]
     */
    private static function emptyNode(string $name): array
    {
        return [
            'breakpointCount' => 0,
            'limiterCount'    => 0,
            'name'            => $name,
            'processTime'     => NULL,
            'repeaterCount'   => 0,
            'requestTime'     => NULL,
            'trashCount'      => 0,
        ];
    }

}
