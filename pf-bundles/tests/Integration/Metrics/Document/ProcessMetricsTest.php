<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Metrics\Document\ProcessesMetrics;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class ProcessMetricsTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Document
 */
final class ProcessMetricsTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Document\ProcessesMetrics::getFields
     * @covers \Hanaboso\PipesFramework\Metrics\Document\ProcessesMetrics::getTags
     * @covers \Hanaboso\PipesFramework\Metrics\Document\ProcessesMetricsFields::getCreated
     * @covers \Hanaboso\PipesFramework\Metrics\Document\ProcessesMetricsFields::getDuration
     * @covers \Hanaboso\PipesFramework\Metrics\Document\ProcessesMetricsFields::isSuccess
     *
     * @throws Exception
     */
    public function testDocument(): void
    {
        $dm = self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');
        $dm->getSchemaManager()->dropDocumentCollection(ProcessesMetrics::class);
        $dm->getSchemaManager()->createDocumentCollection(ProcessesMetrics::class);
        $dm->createQueryBuilder(ProcessesMetrics::class)
            ->insert()
            ->setNewObj(
                [
                    'fields' => [
                        'result'   => TRUE,
                        'duration' => 10,
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

        /** @var DocumentRepository<ProcessesMetrics> $repository */
        $repository = $dm->getRepository(ProcessesMetrics::class);
        /** @var ProcessesMetrics $result */
        $result = $repository->findAll()[0];
        self::assertTrue($result->getFields()->isSuccess());
        self::assertEquals(10, $result->getFields()->getDuration());
        self::assertEquals(DateTimeUtils::getUtcDateTime('1.1.2020'), $result->getFields()->getCreated());
        self::assertEquals('1', $result->getTags()->getNodeId());
    }

}
