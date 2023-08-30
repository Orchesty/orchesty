<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Document;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyProgressTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Document
 *
 * @covers  \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress
 */
final class TopologyProgressTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDocument(): void
    {
        $t = DateTimeUtils::getUtcDateTime();

        $progress = new TopologyProgress();
        $progress
            ->setTopologyId('id')
            ->setCorrelationId('c-id')
            ->setOk(1)
            ->setNok(1)
            ->setTotal(2)
            ->setCorrelationId('c-id')
            ->setStartedAt($t)
            ->setFinishedAt($t);
        $this->setProperty($progress, 'id', '123');

        self::assertEquals('123', $progress->getId());
        self::assertEquals('id', $progress->getTopologyId());
        self::assertEquals('c-id', $progress->getCorrelationId());
        self::assertEquals(1, $progress->getOk());
        self::assertEquals(1, $progress->getNok());
        self::assertEquals(2, $progress->getTotal());
        self::assertEquals($t, $progress->getFinishedAt());
        self::assertEquals($t, $progress->getStartedAt());
        self::assertEquals(
            [
                'id'             => 'id',
                'correlationId'  => 'c-id',
                'duration'       => 0,
                'status'         => 'FAILED',
                'nodesProcessed' => 2,
                'nodesTotal'     => 2,
                'started'        => $t->format(DateTimeUtils::DATE_TIME_UTC),
                'finished'       => $t->format(DateTimeUtils::DATE_TIME_UTC),
                'failed'         => 1,
            ],
            $progress->toArray(),
        );
    }

}
