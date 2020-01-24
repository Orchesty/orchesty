<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Document;

use Exception;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use PhpAmqpLib\Message\AMQPMessage;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class LongRunningNodeDataTest
 *
 * @package PipesPhpSdkTests\Integration\LongRunningNode\Document
 */
final class LongRunningNodeDataTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::fromMessage
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getParentId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setParentId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getCorrelationId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setCorrelationId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getSequenceId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setSequenceId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getTopologyId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getNodeId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setNodeId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getState
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setState
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getData
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getUpdatedBy
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setUpdatedBy
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setAuditLogs
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::preFlush
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::postLoad
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::toProcessDto
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::toArray
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getContentType
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setContentType
     *
     * @throws Exception
     */
    public function testNodeDocument(): void
    {
        $document = (new LongRunningNodeData())
            ->setParentId('1')
            ->setCorrelationId('2')
            ->setSequenceId('3')
            ->setState('state')
            ->setAuditLogs([])
            ->setUpdatedBy('4')
            ->setTopologyId('5')
            ->setNodeId('6')
            ->setData('data')
            ->setContentType('string');
        $this->pfd($document);

        $document = $this->dm->getRepository(LongRunningNodeData::class)->findAll()[0];
        $this->dm->refresh($document);

        self::assertEquals('1', $document->getParentId());
        self::assertEquals('2', $document->getCorrelationId());
        self::assertEquals('3', $document->getSequenceId());
        self::assertEquals('5', $document->getTopologyId());
        self::assertEquals('6', $document->getNodeId());
        self::assertEquals('state', $document->getState());
        self::assertEquals('data', $document->getData());
        self::assertEquals('data', $document->toProcessDto()->getData());
        self::assertEquals(17, count($document->toArray()));
        self::assertEquals('4', $document->getUpdatedBy());
        self::assertEquals('string', $document->getContentType());
        self::assertEquals('', LongRunningNodeData::fromMessage(new AMQPMessage())->getData());
    }

}
