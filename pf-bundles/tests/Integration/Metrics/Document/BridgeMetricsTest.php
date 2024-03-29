<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics;
use Hanaboso\Utils\Date\DateTimeUtils;
use MongoDB\BSON\UTCDateTime;
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
        $dm = self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');
        $dm->getSchemaManager()->dropDocumentCollection(BridgesMetrics::class);
        $dm->getSchemaManager()->createDocumentCollection(BridgesMetrics::class);
        $dm->createQueryBuilder(BridgesMetrics::class)
            ->insert()
            ->setNewObj(
                [
                    'fields' => [
                        'created'          => new UTCDateTime(DateTimeUtils::getUtcDateTime('1.1.2020')),
                        'result_success'   => TRUE,
                        'total_duration'   => 20,
                        'waiting_duration' => 10,
                    ],
                    'tags'   => [
                        'node_id'     => '1',
                        'queue'       => '12',
                        'topology_id' => '2',
                    ],
                ],
            )
            ->getQuery()
            ->execute();

        /** @var DocumentRepository<BridgesMetrics> $repository */
        $repository = $dm->getRepository(BridgesMetrics::class);
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
