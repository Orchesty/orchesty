<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Metrics\Document\MonolithMetrics;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class MonolithMetricsTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Document
 */
final class MonolithMetricsTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Document\MonolithMetrics::getFields
     * @covers \Hanaboso\PipesFramework\Metrics\Document\MonolithMetrics::getTags
     * @covers \Hanaboso\PipesFramework\Metrics\Document\MonolithMetricsFields::getCreated
     * @covers \Hanaboso\PipesFramework\Metrics\Document\MonolithMetricsFields::getUserTime
     * @covers \Hanaboso\PipesFramework\Metrics\Document\MonolithMetricsFields::getKernelTime
     *
     * @throws Exception
     */
    public function testDocument(): void
    {
        $this->dm->createQueryBuilder(MonolithMetrics::class)
            ->insert()
            ->setNewObj(
                [
                    'fields' => [
                        'fpm_cpu_kernel_time' => '1.111',
                        'fpm_cpu_user_time'   => '2.222',
                        'created'             => DateTimeUtils::getUtcDateTime('1.1.2020'),
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

        /** @var DocumentRepository<MonolithMetrics> $repository */
        $repository = $this->dm->getRepository(MonolithMetrics::class);
        /** @var MonolithMetrics $result */
        $result = $repository->findAll()[0];
        self::assertEquals('1.111', $result->getFields()->getKernelTime());
        self::assertEquals('2.222', $result->getFields()->getUserTime());
        self::assertEquals(DateTimeUtils::getUtcDateTime('1.1.2020'), $result->getFields()->getCreated());
        self::assertEquals('1', $result->getTags()->getNodeId());
    }

}
