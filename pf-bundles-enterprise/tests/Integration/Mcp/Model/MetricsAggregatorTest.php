<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\Mcp\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\ProcessHandler;
use Hanaboso\PipesFramework\HbPFMetricsBundle\Handler\MetricsHandler;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Model\MetricsAggregator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class MetricsAggregatorTest
 *
 * Unit-level coverage of the aggregator's response shaping logic. Real grid
 * filters / Mongo are stubbed via mocks so we can assert the response shape,
 * date-window propagation and the failure-rate ranking deterministically.
 *
 * @package PipesFrameworkEnterpriseTests\Integration\Mcp\Model
 */
#[CoversClass(MetricsAggregator::class)]
#[AllowMockObjectsWithoutExpectations]
final class MetricsAggregatorTest extends TestCase
{

    /**
     * Verifies that the processes timeseries response carries the expected shape and totals.
     */
    public function testProcessesTimeseriesShape(): void
    {
        $processHandler = $this->createMock(ProcessHandler::class);
        $processHandler
            ->expects(self::once())
            ->method('getProcessesGraph')
            ->willReturn([
                'items' => [
                    ['created' => '2026-04-20T00:00:00Z', 'success' => 5, 'failed' => 1],
                    ['created' => '2026-04-21T00:00:00Z', 'success' => 3, 'failed' => 2],
                    // duplicate bucket — must be summed.
                    ['created' => '2026-04-21T00:00:00Z', 'success' => 1, 'failed' => 0],
                ],
            ]);

        $aggregator = new MetricsAggregator(
            $processHandler,
            $this->createMock(MetricsHandler::class),
            $this->createMock(DocumentManager::class),
        );

        $result = $aggregator->getProcessesTimeseries(['period' => 'last_7d']);

        self::assertSame('timeseries', $result['kind']);
        self::assertCount(2, $result['points']);
        self::assertSame(['failed' => 1, 'success' => 5, 'time' => '2026-04-20T00:00:00Z'], $result['points'][0]);
        self::assertSame(['failed' => 2, 'success' => 4, 'time' => '2026-04-21T00:00:00Z'], $result['points'][1]);
        self::assertSame(12, $result['total']);
        self::assertSame(3, $result['failed']);
        self::assertSame(9, $result['success']);
        self::assertNull($result['topologyId']);
        self::assertStringContainsString('all topologies', $result['title']);
    }

    /**
     * Verifies that the topology id and bucket count are forwarded to the underlying handler.
     */
    public function testProcessesTimeseriesPassesTopologyAndBuckets(): void
    {
        $processHandler = $this->createMock(ProcessHandler::class);
        $processHandler
            ->expects(self::once())
            ->method('getProcessesGraph')
            ->with(self::anything(), 6)
            ->willReturn(['items' => []]);

        $aggregator = new MetricsAggregator(
            $processHandler,
            $this->createMock(MetricsHandler::class),
            $this->createMock(DocumentManager::class),
        );

        $result = $aggregator->getProcessesTimeseries([
            'buckets'     => 6,
            'period'      => 'today',
            'topology_id' => 'topo-1',
        ]);

        self::assertSame('topo-1', $result['topologyId']);
        self::assertStringContainsString('topo-1', $result['title']);
        self::assertSame([], $result['points']);
        self::assertSame(0, $result['total']);
    }

    /**
     * Verifies that an out-of-range bucket count is clamped to the maximum value.
     */
    public function testBucketsCappedToMax(): void
    {
        $processHandler = $this->createMock(ProcessHandler::class);
        $processHandler
            ->expects(self::once())
            ->method('getProcessesGraph')
            ->with(self::anything(), 24)
            ->willReturn(['items' => []]);

        $aggregator = new MetricsAggregator(
            $processHandler,
            $this->createMock(MetricsHandler::class),
            $this->createMock(DocumentManager::class),
        );

        $aggregator->getProcessesTimeseries(['period' => 'last_30d', 'buckets' => 999]);
    }

    /**
     * Verifies that failing connectors are ranked by failure count and the list is capped to the limit.
     */
    public function testFailingConnectorsRanksAndCapsList(): void
    {
        $items = [
            ['nodeId' => 'n-flaky', 'topologyId' => 't-1', 'count' => 100, 'status400' => 30, 'status500' => 5],
            ['nodeId' => 'n-fine',  'topologyId' => 't-1', 'count' => 50,  'status400' => 0,  'status500' => 0],
            ['nodeId' => 'n-down',  'topologyId' => 't-2', 'count' => 20,  'status400' => 5,  'status500' => 10],
        ];

        $metricsHandler = $this->createMock(MetricsHandler::class);
        $metricsHandler
            ->expects(self::once())
            ->method('getMetricsConnectorsOverview')
            ->willReturn(['items' => $items]);

        $aggregator = new MetricsAggregator(
            $this->createMock(ProcessHandler::class),
            $metricsHandler,
            $this->mockDmWithoutDocs(),
        );

        $result = $aggregator->getFailingConnectors(['period' => 'today', 'limit' => 5]);

        self::assertSame('list', $result['kind']);
        self::assertCount(2, $result['items']);
        // Most failures first.
        self::assertSame('n-flaky', $result['items'][0]['nodeId']);
        self::assertSame(35, $result['items'][0]['failed']);
        self::assertSame(65, $result['items'][0]['success']);
        self::assertSame(0.35, $result['items'][0]['failureRate']);
        self::assertSame('n-down', $result['items'][1]['nodeId']);
        self::assertSame(15, $result['items'][1]['failed']);
    }

    /**
     * Verifies that the failing connectors limit argument is honoured (and clamped to MAX_LIMIT).
     */
    public function testFailingConnectorsRespectsLimit(): void
    {
        $items = [];
        for ($i = 0; $i < 25; $i++) {
            $items[] = [
                'count'      => 10,
                'nodeId'     => sprintf('n-%d', $i),
                'status400'  => 1,
                'status500'  => 0,
                'topologyId' => 't-1',
            ];
        }

        $metricsHandler = $this->createMock(MetricsHandler::class);
        $metricsHandler
            ->method('getMetricsConnectorsOverview')
            ->willReturn(['items' => $items]);

        $aggregator = new MetricsAggregator(
            $this->createMock(ProcessHandler::class),
            $metricsHandler,
            $this->mockDmWithoutDocs(),
        );

        $clamped = $aggregator->getFailingConnectors(['period' => 'today', 'limit' => 999]);
        self::assertCount(20, $clamped['items']);

        $explicit = $aggregator->getFailingConnectors(['period' => 'today', 'limit' => 3]);
        self::assertCount(3, $explicit['items']);
    }

    /**
     * Verifies that recent error items are built from connector metrics rows.
     */
    public function testRecentErrorsBuildsItemsFromConnectorMetrics(): void
    {
        $rows = [
            [
                'correlationId' => 'cid-1',
                'created'       => '2026-04-26T20:35:00Z',
                'message'       => 'Bad request: missing email',
                'nodeId'        => 'n-post-order',
                'status'        => 400,
                'topologyId'    => 't-1',
            ],
            [
                'correlationId' => 'cid-2',
                'created'       => '2026-04-26T20:30:00Z',
                'message'       => 'Upstream 502',
                'nodeId'        => 'n-ship',
                'status'        => 502,
                'topologyId'    => 't-2',
            ],
        ];

        $metricsHandler = $this->createMock(MetricsHandler::class);
        $metricsHandler
            ->expects(self::once())
            ->method('getMetricsConnectors')
            ->with(self::callback(static function (GridRequestDtoInterface $dto): bool {
                $filter = $dto->getFilter();
                if ($filter === [] || !is_array($filter[0] ?? NULL)) {
                    return FALSE;
                }

                $hasFailed = FALSE;
                $hasRange  = FALSE;
                foreach ($filter[0] as $cond) {
                    if (($cond['column'] ?? NULL) === 'status'
                        && ($cond['operator'] ?? NULL) === GridFilterAbstract::EQ
                        && ($cond['value'][0] ?? NULL) === 'FAILED'
                    ) {
                        $hasFailed = TRUE;
                    }

                    if (($cond['column'] ?? NULL) === 'created'
                        && ($cond['operator'] ?? NULL) === GridFilterAbstract::BETWEEN
                    ) {
                        $hasRange = TRUE;
                    }
                }

                return $hasFailed && $hasRange;
            }))
            ->willReturn(['items' => $rows]);

        $aggregator = new MetricsAggregator(
            $this->createMock(ProcessHandler::class),
            $metricsHandler,
            $this->mockDmWithoutDocs(),
        );

        $result = $aggregator->getRecentErrors(['period' => 'last_7d']);

        self::assertSame('list', $result['kind']);
        self::assertSame('Recent errors', $result['title']);
        self::assertCount(2, $result['items']);

        self::assertSame('cid-1', $result['items'][0]['correlationId']);
        self::assertSame('failed', $result['items'][0]['resultStatus']);
        self::assertSame(400, $result['items'][0]['httpStatus']);
        self::assertSame('Bad request: missing email', $result['items'][0]['resultMessage']);
        self::assertSame('2026-04-26T20:35:00Z', $result['items'][0]['finishedAt']);

        self::assertSame('cid-2', $result['items'][1]['correlationId']);
        self::assertSame(502, $result['items'][1]['httpStatus']);
    }

    /**
     * Verifies that the topology_id post-filter is applied to the recent errors result set.
     */
    public function testRecentErrorsRespectsTopologyPostFilter(): void
    {
        $rows = [
            ['correlationId' => 'cid-A', 'created' => '2026-04-26T20:35:00Z', 'message' => 'a', 'nodeId' => 'n-1', 'status' => 500, 'topologyId' => 't-other'],
            ['correlationId' => 'cid-B', 'created' => '2026-04-26T20:34:00Z', 'message' => 'b', 'nodeId' => 'n-2', 'status' => 500, 'topologyId' => 't-target'],
            ['correlationId' => 'cid-C', 'created' => '2026-04-26T20:33:00Z', 'message' => 'c', 'nodeId' => 'n-3', 'status' => 500, 'topologyId' => 't-target'],
        ];

        $metricsHandler = $this->createMock(MetricsHandler::class);
        $metricsHandler
            ->expects(self::once())
            ->method('getMetricsConnectors')
            ->willReturn(['items' => $rows]);

        $aggregator = new MetricsAggregator(
            $this->createMock(ProcessHandler::class),
            $metricsHandler,
            $this->mockDmWithoutDocs(),
        );

        $result = $aggregator->getRecentErrors([
            'limit'       => 999,
            'period'      => 'today',
            'topology_id' => 't-target',
        ]);

        self::assertCount(2, $result['items']);
        self::assertSame('cid-B', $result['items'][0]['correlationId']);
        self::assertSame('cid-C', $result['items'][1]['correlationId']);
        self::assertStringContainsString('t-target', $result['title']);
    }

    /**
     * Verifies that the recent errors limit argument is honoured.
     */
    public function testRecentErrorsRespectsLimit(): void
    {
        $rows = [];
        for ($i = 0; $i < 15; $i++) {
            $rows[] = [
                'correlationId' => sprintf('cid-%d', $i),
                'created'       => sprintf('2026-04-26T20:%02d:00Z', $i),
                'message'       => sprintf('err %d', $i),
                'nodeId'        => 'n',
                'status'        => 500,
                'topologyId'    => 't-1',
            ];
        }

        $metricsHandler = $this->createMock(MetricsHandler::class);
        $metricsHandler->method('getMetricsConnectors')->willReturn(['items' => $rows]);

        $aggregator = new MetricsAggregator(
            $this->createMock(ProcessHandler::class),
            $metricsHandler,
            $this->mockDmWithoutDocs(),
        );

        $result = $aggregator->getRecentErrors(['period' => 'today', 'limit' => 3]);

        self::assertCount(3, $result['items']);
    }

    /**
     * Verifies that recent errors returns an empty list when the metrics handler yields no rows.
     */
    public function testRecentErrorsEmptyWhenNoMetrics(): void
    {
        $metricsHandler = $this->createMock(MetricsHandler::class);
        $metricsHandler->method('getMetricsConnectors')->willReturn(['items' => []]);

        $aggregator = new MetricsAggregator(
            $this->createMock(ProcessHandler::class),
            $metricsHandler,
            $this->mockDmWithoutDocs(),
        );

        $result = $aggregator->getRecentErrors(['period' => 'today']);

        self::assertSame('list', $result['kind']);
        self::assertSame([], $result['items']);
    }

    /**
     * @return DocumentManager
     */
    private function mockDmWithoutDocs(): DocumentManager
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            static function (string $class) use ($repo) {
                self::assertContains($class, [Node::class, Topology::class]);

                return $repo;
            },
        );

        return $dm;
    }

}
