<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Model;

use Exception;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Configurator\Document\HourlyMultiCounterAggregate;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Configurator\Model\ProcessManager;
use Hanaboso\Utils\Date\DateTimeUtils;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class ProcessManagerTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Model
 */
#[CoversClass(ProcessManager::class)]
final class ProcessManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetProcessesTopologiesAggregatedSumsHourlyBuckets(): void
    {
        $this->seedHourlyMultiCounterAggregate('-3 hours', 100, 5, lastStatus: 'COMPLETED');
        $this->seedHourlyMultiCounterAggregate('-2 hours', 50, 2, lastStatus: 'FAILED');
        $this->seedHourlyMultiCounterAggregate('-2 hours', 10, 0, topologyId: 'topo-2');

        $result = $this->getManager()->getProcessesTopologies($this->createdGteDto('-1 days'), TRUE);

        self::assertCount(2, $result);

        $byTopology = array_column($result, NULL, 'topologyId');

        self::assertEquals(150, $byTopology['topo-1']['count']);
        self::assertEquals(7, $byTopology['topo-1']['failedCount']);
        self::assertEquals('FAILED', $byTopology['topo-1']['status']);
        self::assertEquals(10, $byTopology['topo-2']['count']);
        self::assertEquals(0, $byTopology['topo-2']['failedCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetProcessesTopologiesAggregatedFallsBackToRawForShortWindow(): void
    {
        $this->seedHourlyMultiCounterAggregate('-10 minutes', 50, 1);

        $result = $this->getManager()->getProcessesTopologies($this->createdGteDto('-30 minutes'), TRUE);

        self::assertSame([], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetProcessesTopologiesAggregatedMatchesRawShape(): void
    {
        $this->seedProcess('proc-1', '2026-06-17T10:05:00Z', 'COMPLETED');
        $this->seedProcess('proc-2', '2026-06-17T10:20:00Z', 'FAILED');
        $this->seedProcess('proc-3', '2026-06-17T10:25:00Z', 'COMPLETED');

        $this->seedHourlyMultiCounterAggregate(
            '2026-06-17T10:00:00Z',
            1,
            0,
            lastCreated: '2026-06-17T10:05:00Z',
            lastStatus: 'COMPLETED',
        );
        $this->seedHourlyMultiCounterAggregate(
            '2026-06-17T10:15:00Z',
            2,
            1,
            lastCreated: '2026-06-17T10:25:00Z',
            lastStatus: 'COMPLETED',
        );

        $dto        = fn(): GridRequestDto => $this->createdBetweenDto('2026-06-17T00:00:00Z', '2026-06-18T00:00:00Z');
        $raw        = $this->getManager()->getProcessesTopologies($dto());
        $aggregated = $this->getManager()->getProcessesTopologies($dto(), TRUE);

        self::assertCount(1, $raw);
        self::assertCount(1, $aggregated);

        foreach (['topologyId', 'created', 'count', 'failedCount', 'status'] as $key) {
            self::assertEquals($raw[0][$key], $aggregated[0][$key], sprintf('Key `%s` differs.', $key));
        }

        self::assertEquals(3, $aggregated[0]['count']);
        self::assertEquals(1, $aggregated[0]['failedCount']);
        self::assertEquals('COMPLETED', $aggregated[0]['status']);
        self::assertEquals('2026-06-17T10:25:00Z', $aggregated[0]['created']);
    }

    /**
     * @return ProcessManager
     */
    private function getManager(): ProcessManager
    {
        /** @var ProcessManager $manager */
        $manager = self::getContainer()->get('hbpf.configurator.manager.process');

        return $manager;
    }

    /**
     * @param string      $bucketOffset
     * @param int         $count
     * @param int         $failed
     * @param string      $topologyId
     * @param string|null $lastCreated
     * @param string      $lastStatus
     *
     * @throws Exception
     */
    private function seedHourlyMultiCounterAggregate(
        string $bucketOffset,
        int $count,
        int $failed,
        string $topologyId = 'topo-1',
        ?string $lastCreated = NULL,
        string $lastStatus = 'COMPLETED',
    ): void
    {
        $this->dm->getDocumentCollection(HourlyMultiCounterAggregate::class)->insertOne(
            [
                'bucket'       => new UTCDateTime(DateTimeUtils::getUtcDateTime($bucketOffset)),
                'count'        => $count,
                'failed'       => $failed,
                'last_created' => new UTCDateTime(DateTimeUtils::getUtcDateTime($lastCreated ?? $bucketOffset)),
                'last_status'  => $lastStatus,
                'topology_id'  => $topologyId,
            ],
        );
    }

    /**
     * @param string $id
     * @param string $created
     * @param string $status
     *
     * @throws Exception
     */
    private function seedProcess(string $id, string $created, string $status): void
    {
        $this->dm->getDocumentCollection(TopologyProgress::class)->insertOne(
            [
                'created'        => new UTCDateTime(DateTimeUtils::getUtcDateTime($created)),
                'duration'       => 1_000,
                'finished'       => new UTCDateTime(DateTimeUtils::getUtcDateTime($created)),
                'nok'            => $status === 'FAILED' ? 1 : 0,
                'ok'             => 1,
                'processedCount' => 1,
                'source'         => 'auto',
                'status'         => $status,
                'topologyId'     => 'topo-1',
                'total'          => 1,
                'user'           => NULL,
                '_id'            => $id,
            ],
        );
    }

    /**
     * @param string $createdOffset
     *
     * @return GridRequestDto
     */
    private function createdGteDto(string $createdOffset): GridRequestDto
    {
        return new GridRequestDto(
            [
                'filter' => [
                    [
                        [
                            'column'   => 'created',
                            'operator' => 'GTE',
                            'value'    => [
                                DateTimeUtils::getUtcDateTime($createdOffset)->format(DateTimeUtils::DATE_TIME_UTC),
                            ],
                        ],
                    ],
                ],
                'paging' => [
                    'itemsPerPage' => 100,
                    'page'         => 1,
                ],
            ],
        );
    }

    /**
     * @param string $createdFrom
     * @param string $createdTo
     *
     * @return GridRequestDto
     */
    private function createdBetweenDto(string $createdFrom, string $createdTo): GridRequestDto
    {
        return new GridRequestDto(
            [
                'filter' => [
                    [
                        [
                            'column'   => 'created',
                            'operator' => 'BETWEEN',
                            'value'    => [
                                DateTimeUtils::getUtcDateTime($createdFrom)->format(DateTimeUtils::DATE_TIME_UTC),
                                DateTimeUtils::getUtcDateTime($createdTo)->format(DateTimeUtils::DATE_TIME_UTC),
                            ],
                        ],
                    ],
                ],
                'paging' => [
                    'itemsPerPage' => 100,
                    'page'         => 1,
                ],
            ],
        );
    }

}
