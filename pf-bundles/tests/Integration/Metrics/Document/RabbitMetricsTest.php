<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Metrics\Document\RabbitMetrics;
use Hanaboso\PipesFramework\Metrics\Document\RabbitMetricsFields;
use Hanaboso\Utils\Date\DateTimeUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class RabbitMetricsTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Document
 */
#[CoversClass(RabbitMetrics::class)]
#[CoversClass(RabbitMetricsFields::class)]
final class RabbitMetricsTest extends DatabaseTestCaseAbstract
{

    /**
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
                        'created'  => DateTimeUtils::getUtcDateTime('1.1.2020')->getTimestamp(),
                        'messages' => 2,
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

        /** @var DocumentRepository<RabbitMetrics> $repository */
        $repository = $dm->getRepository(RabbitMetrics::class);
        /** @var RabbitMetrics $result */
        $result = $repository->findAll()[0];
        self::assertSame(2, $result->getFields()->getMessages());
        self::assertEquals(DateTimeUtils::getUtcDateTime('1.1.2020'), $result->getFields()->getCreated());
        self::assertSame('1', $result->getTags()->getNodeId());
    }

}
