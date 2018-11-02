<?php declare(strict_types=1);

namespace Tests\Integration\LongRunningNode\Model;

use EmailServiceBundle\Utils\PipesHeaders;
use Exception;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeManager;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class LongRunningNodeManagerTest
 *
 * @package Tests\Integration\LongRunningNode\Model
 */
final class LongRunningNodeManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers LongRunningNodeManager::getDocument()
     * @covers LongRunningNodeManager::saveDocument()
     *
     * @throws Exception
     */
    public function testManager(): void
    {
        /** @var LongRunningNodeManager $manager */
        $manager = $this->ownContainer->get('hbpf.manager.long_running');
        $doc     = new LongRunningNodeData();
        $doc->setProcessId('proc')
            ->setNodeId('node')
            ->setAuditLogs(['audit1', 'audit2'])
            ->setTopologyId('topo')
            ->setParentProcess('parent')
            ->setData('data')
            ->setHeaders(['head']);

        $manager->saveDocument($doc);
        /** @var LongRunningNodeData $doc */
        $docs = $this->dm->getRepository(LongRunningNodeData::class)->findAll();
        $doc  = reset($docs);
        self::assertEquals(['audit1', 'audit2'], $doc->getAuditLogs());
        self::assertEquals('proc', $doc->getProcessId());
        self::assertEquals('parent', $doc->getParentProcess());
        self::assertEquals(['head'], $doc->getHeaders());
        self::assertEquals('node', $doc->getNodeId());
        self::assertEquals('topo', $doc->getTopologyId());

        $headers                                                                   = [];
        $headers[PipesHeaders::createKey(LongRunningNodeData::DOCUMENT_ID_HEADER)] = $doc->getid();
        $headers[PipesHeaders::createKey(PipesHeaders::NODE_ID)]                   = 'newNode';

        $doc = new LongRunningNodeData();
        $doc->setProcessId('proc')
            ->setNodeId('node')
            ->setAuditLogs(['audit3'])
            ->setTopologyId('topo')
            ->setParentProcess('parent')
            ->setData('data2')
            ->setHeaders(['head2']);
        $manager->saveDocument($doc);

        $this->dm->clear();
        /** @var LongRunningNodeData $doc */
        $docs = $this->dm->getRepository(LongRunningNodeData::class)->findAll();
        self::assertEquals(1, count($docs));
        $doc = reset($docs);
        self::assertEquals(['audit1', 'audit2', 'audit3'], $doc->getAuditLogs());
        self::assertEquals('data2', $doc->getData());
        self::assertEquals('proc', $doc->getProcessId());
        self::assertEquals('node', $doc->getNodeId());
        self::assertEquals('topo', $doc->getTopologyId());

        self::assertNotNull($manager->getDocument('topo', 'node'));
        self::assertNotNull($manager->getDocument('topo', 'node', 'proc'));
        self::assertEquals(1, count($this->dm->getRepository(LongRunningNodeData::class)->findAll()));
    }

}