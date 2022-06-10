<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class ConnectorsMetricsTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Document
 */
final class ConnectorsMetricsTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics::getTags
     * @covers \Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics::getFields
     * @covers \Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetricsFields::getTotalDuration
     * @covers \Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetricsFields::getCreated
     *
     * @throws Exception
     */
    public function testDocument(): void
    {
        $dm = self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');
        $dm->getSchemaManager()->dropDocumentCollection(ConnectorsMetrics::class);
        $dm->getSchemaManager()->createDocumentCollection(ConnectorsMetrics::class);
        $dm->createQueryBuilder(ConnectorsMetrics::class)
            ->insert()
            ->setNewObj(
                [
                    'fields' => [
                        'sent_request_total_duration' => 10,
                        'created'                     => DateTimeUtils::getUtcDateTime('1.1.2020')->getTimestamp(),
                    ],
                    'tags'   => [
                        'node_id'     => '1',
                        'topology_id' => '2',
                        'queue'       => '12',
                    ],
                ],
            )
            ->getQuery()
            ->execute();

        /** @var DocumentRepository<ConnectorsMetrics> $repository */
        $repository = $dm->getRepository(ConnectorsMetrics::class);
        /** @var ConnectorsMetrics $result */
        $result = $repository->findAll()[0];
        self::assertEquals(10, $result->getFields()->getTotalDuration());
        self::assertEquals(DateTimeUtils::getUtcDateTime('1.1.2020'), $result->getFields()->getCreated());
        self::assertEquals('1', $result->getTags()->getNodeId());
    }

}
