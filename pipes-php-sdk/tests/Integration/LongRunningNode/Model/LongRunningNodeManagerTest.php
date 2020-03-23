<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Model;

use Exception;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class LongRunningNodeManagerTest
 *
 * @package PipesPhpSdkTests\Integration\LongRunningNode\Model
 */
final class LongRunningNodeManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager::getDocument
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager::saveDocument
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager::update
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager::delete
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setProcessId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setNodeName
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setTopologyName
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setTopologyId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setParentProcess
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setData
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::setHeaders
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getAuditLogs
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getProcessId
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getParentProcess
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getHeaders
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getNodeName
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData::getTopologyName
     *
     * @throws Exception
     */
    public function testManager(): void
    {
        /** @var LongRunningNodeManager $manager */
        $manager = self::$container->get('hbpf.manager.long_running');
        $doc     = new LongRunningNodeData();
        $doc->setProcessId('proc')
            ->setNodeName('node')
            ->setNodeId('node')
            ->setAuditLogs(['audit1', 'audit2'])
            ->setTopologyName('topo-name-manager')
            ->setTopologyId('topo-id-manager')
            ->setParentProcess('parent')
            ->setData('data')
            ->setHeaders(['head']);
        $manager->saveDocument($doc);
        $this->dm->clear();

        $manager->update($doc, ['data' => 'something']);

        $docs = $this->dm->getRepository(LongRunningNodeData::class)->findAll();
        /** @var LongRunningNodeData $doc */
        $doc = reset($docs);
        self::assertEquals(['audit1', 'audit2'], $doc->getAuditLogs());
        self::assertEquals('proc', $doc->getProcessId());
        self::assertEquals('parent', $doc->getParentProcess());
        self::assertEquals(['head'], $doc->getHeaders());
        self::assertEquals('node', $doc->getNodeName());
        self::assertEquals('topo-name-manager', $doc->getTopologyName());

        $doc = new LongRunningNodeData();
        $doc->setProcessId('proc')
            ->setNodeName('node')
            ->setNodeId('node')
            ->setAuditLogs(['audit3'])
            ->setTopologyName('topo-name-manager2')
            ->setTopologyId('topo-id-manager2')
            ->setParentProcess('parent')
            ->setData('data2')
            ->setHeaders(['head2']);
        $manager->saveDocument($doc);
        $this->dm->clear();

        $repository = $this->dm->getRepository(LongRunningNodeData::class);
        $docs       = $repository->findAll();
        self::assertEquals(2, count($docs));
        /** @var LongRunningNodeData $doc */
        $doc = reset($docs);
        self::assertEquals(['audit1', 'audit2'], $doc->getAuditLogs());
        self::assertEquals('data', $doc->getData());
        self::assertEquals('proc', $doc->getProcessId());
        self::assertEquals('node', $doc->getNodeName());
        self::assertEquals('topo-name-manager', $doc->getTopologyName());

        self::assertNotNull($manager->getDocument('topo-id-manager2', 'node'));
        self::assertNotNull($manager->getDocument('topo-id-manager2', 'node', 'proc'));

        $manager->delete($doc);

        $docs = $repository->findAll();
        self::assertEquals(1, count($docs));
    }

}
