<?php declare(strict_types=1);

namespace Tests\Integration\LongRunningNode\Model;

use Exception;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager;
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
        $manager = self::$container->get('hbpf.manager.long_running');
        $doc     = new LongRunningNodeData();
        $doc->setProcessId('proc')
            ->setNodeName('node')
            ->setNodeId('node')
            ->setAuditLogs(['audit1', 'audit2'])
            ->setTopologyName('topo')
            ->setTopologyId('topo')
            ->setParentProcess('parent')
            ->setData('data')
            ->setHeaders(['head']);

        $manager->saveDocument($doc);
        $docs = $this->dm->getRepository(LongRunningNodeData::class)->findAll();
        /** @var LongRunningNodeData $doc */
        $doc = reset($docs);
        self::assertEquals(['audit1', 'audit2'], $doc->getAuditLogs());
        self::assertEquals('proc', $doc->getProcessId());
        self::assertEquals('parent', $doc->getParentProcess());
        self::assertEquals(['head'], $doc->getHeaders());
        self::assertEquals('node', $doc->getNodeName());
        self::assertEquals('topo', $doc->getTopologyName());

        $headers                                                                   = [];
        $headers[PipesHeaders::createKey(LongRunningNodeData::DOCUMENT_ID_HEADER)] = $doc->getid();
        $headers[PipesHeaders::createKey(PipesHeaders::NODE_ID)]                   = 'newNode';

        $doc = new LongRunningNodeData();
        $doc->setProcessId('proc')
            ->setNodeName('node')
            ->setNodeId('node')
            ->setAuditLogs(['audit3'])
            ->setTopologyName('topo')
            ->setTopologyId('topo')
            ->setParentProcess('parent')
            ->setData('data2')
            ->setHeaders(['head2']);
        $manager->saveDocument($doc);

        $this->dm->clear();
        $docs = $this->dm->getRepository(LongRunningNodeData::class)->findAll();
        self::assertEquals(2, count($docs));
        /** @var LongRunningNodeData $doc */
        $doc = reset($docs);
        self::assertEquals(['audit1', 'audit2'], $doc->getAuditLogs());
        self::assertEquals('data', $doc->getData());
        self::assertEquals('proc', $doc->getProcessId());
        self::assertEquals('node', $doc->getNodeName());
        self::assertEquals('topo', $doc->getTopologyName());

        self::assertNotNull($manager->getDocument('topo', 'node'));
        self::assertNotNull($manager->getDocument('topo', 'node', 'proc'));
        self::assertEquals(2, count($this->dm->getRepository(LongRunningNodeData::class)->findAll()));
    }

}
