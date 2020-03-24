<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class BridgeMetricsTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Document
 */
final class BridgeMetricsTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics::getFields
     * @covers \Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics::getTags
     * @covers \Hanaboso\PipesFramework\Metrics\Document\BridgesMetricsFields::isSuccess
     * @covers \Hanaboso\PipesFramework\Metrics\Document\BridgesMetricsFields::getWaitingDuration
     * @covers \Hanaboso\PipesFramework\Metrics\Document\BridgesMetricsFields::getTotalDuration
     * @covers \Hanaboso\PipesFramework\Metrics\Document\BridgesMetricsFields::getCreated
     * @covers \Hanaboso\PipesFramework\Metrics\Document\Tags::getNodeId
     * @covers \Hanaboso\PipesFramework\Metrics\Document\Tags::getTopologyId
     * @covers \Hanaboso\PipesFramework\Metrics\Document\Tags::getQueue
     *
     * @throws Exception
     */
    public function testDocument(): void
    {
        $this->dm->createQueryBuilder(BridgesMetrics::class)
            ->insert()
            ->setNewObj(
                [
                    'fields' => [
                        'bridge_job_result_success'   => TRUE,
                        'bridge_job_waiting_duration' => 10,
                        'bridge_job_total_duration'   => 20,
                        'created'                     => DateTimeUtils::getUtcDateTime('1.1.2020'),
                    ],
                    'tags'   => [
                        'nodeId'     => '1',
                        'topologyId' => '2',
                        'queue'      => '12',
                    ],
                ]
            )
            ->getQuery()
            ->execute();

        /** @var DocumentRepository<BridgesMetrics> $repository */
        $repository = $this->dm->getRepository(BridgesMetrics::class);
        /** @var BridgesMetrics $result */
        $result = $repository->findAll()[0];
        self::assertTrue($result->getFields()->isSuccess());
        self::assertEquals(10, $result->getFields()->getWaitingDuration());
        self::assertEquals(20, $result->getFields()->getTotalDuration());
        self::assertEquals(DateTimeUtils::getUtcDateTime('1.1.2020'), $result->getFields()->getCreated());
        self::assertEquals('1', $result->getTags()->getNodeId());
        self::assertEquals('2', $result->getTags()->getTopologyId());
        self::assertEquals('12', $result->getTags()->getQueue());
    }

}
