<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Document;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\NodeProgress;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyProgressTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Document
 */
final class TopologyProgressTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::setDuration
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getDuration
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::setFinishedAt
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getFinishedAt
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::setStartedAt
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getStartedAt
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::setFollowers
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getFollowers
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::setStatus
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getStatus
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::setCorrelationId
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getCorrelationId
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::setTopologyId
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getTopologyId
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::setTopologyName
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getTopologyName
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::addNode
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::getId
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::__construct
     * @covers \Hanaboso\PipesFramework\Configurator\Document\TopologyProgress::toArray
     * @covers \Hanaboso\PipesFramework\Configurator\Document\NodeProgress::setNodeId
     * @covers \Hanaboso\PipesFramework\Configurator\Document\NodeProgress::getNodeId
     * @covers \Hanaboso\PipesFramework\Configurator\Document\NodeProgress::setNodeName
     * @covers \Hanaboso\PipesFramework\Configurator\Document\NodeProgress::getNodeName
     * @covers \Hanaboso\PipesFramework\Configurator\Document\NodeProgress::setProcessId
     * @covers \Hanaboso\PipesFramework\Configurator\Document\NodeProgress::getProcessId
     * @covers \Hanaboso\PipesFramework\Configurator\Document\NodeProgress::setStatus
     * @covers \Hanaboso\PipesFramework\Configurator\Document\NodeProgress::getStatus
     * @covers \Hanaboso\PipesFramework\Configurator\Document\NodeProgress::toArray
     *
     * @throws Exception
     */
    public function testDocument(): void
    {
        $t = DateTimeUtils::getUtcDateTime();

        $node = new NodeProgress();
        $node
            ->setStatus('OK')
            ->setProcessId('p-id')
            ->setNodeName('name')
            ->setNodeId('id');

        $progress = new TopologyProgress();
        $progress
            ->setTopologyName('name')
            ->setTopologyId('id')
            ->setCorrelationId('c-id')
            ->setStatus('OK')
            ->setFollowers(1)
            ->setStartedAt($t)
            ->setFinishedAt($t)
            ->addNode($node)
            ->setDuration(10);
        $this->setProperty($progress, 'id', '123');

        self::assertEquals('123', $progress->getId());
        self::assertEquals('name', $progress->getTopologyName());
        self::assertEquals('id', $progress->getTopologyId());
        self::assertEquals('c-id', $progress->getCorrelationId());
        self::assertEquals('OK', $progress->getStatus());
        self::assertEquals(1, $progress->getFollowers());
        self::assertEquals($t, $progress->getFinishedAt());
        self::assertEquals($t, $progress->getStartedAt());
        self::assertEquals(10, $progress->getDuration());
        self::assertEquals(1, $progress->getNodes()->count());
        self::assertEquals('name', $node->getNodeName());
        self::assertEquals('id', $node->getNodeId());
        self::assertEquals('OK', $node->getStatus());
        self::assertEquals('p-id', $node->getProcessId());
        self::assertEquals(
            [
                'id'             => 'id',
                'name'           => 'name',
                'correlationId'  => 'c-id',
                'duration'       => 10,
                'status'         => 'OK',
                'nodesProcessed' => 1,
                'nodesRemaining' => 1,
                'nodesTotal'     => 2,
                'started'        => $t->format(DateTimeUtils::DATE_TIME),
                'finished'       => $t->format(DateTimeUtils::DATE_TIME),
                'nodes'          => [
                    [
                        'id'        => 'id',
                        'name'      => 'name',
                        'processId' => 'p-id',
                        'status'    => 'OK',
                    ],
                ],
            ],
            $progress->toArray()
        );
    }

}
