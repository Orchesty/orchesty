<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Metrics\Document\RabbitMetrics;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class RabbitMetricsTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Document
 */
final class RabbitMetricsTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Document\RabbitMetrics::getTags
     * @covers \Hanaboso\PipesFramework\Metrics\Document\RabbitMetrics::getFields
     * @covers \Hanaboso\PipesFramework\Metrics\Document\RabbitMetricsFields::getCreated
     * @covers \Hanaboso\PipesFramework\Metrics\Document\RabbitMetricsFields::getMessages
     *
     * @throws Exception
     */
    public function testDocument(): void
    {
        $dm = self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');
        $dm->getSchemaManager()->dropDocumentCollection(RabbitMetrics::class);
        $dm->getSchemaManager()->createDocumentCollection(RabbitMetrics::class);
        $dm->createQueryBuilder(RabbitMetrics::class)
            ->insert()
            ->setNewObj(
                [
                    'fields' => [
                        'messages' => 2,
                        'created'  => DateTimeUtils::getUtcDateTime('1.1.2020')->getTimestamp(),
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

        /** @var DocumentRepository<RabbitMetrics> $repository */
        $repository = $dm->getRepository(RabbitMetrics::class);
        /** @var RabbitMetrics $result */
        $result = $repository->findAll()[0];
        self::assertEquals(2, $result->getFields()->getMessages());
        self::assertEquals(DateTimeUtils::getUtcDateTime('1.1.2020'), $result->getFields()->getCreated());
        self::assertEquals('1', $result->getTags()->getNodeId());
    }

}
