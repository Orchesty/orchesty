<?php declare(strict_types=1);

namespace Tests\Integration\LongRunningNode\Model;

use EmailServiceBundle\Utils\PipesHeaders;
use Exception;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeManager;
use Hanaboso\PipesFramework\LongRunningNode\Model\MessageDto;
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
        $headers = [
            PipesHeaders::createKey(LongRunningNodeData::PARENT_PROCESS_HEADER) => 'parent',
            PipesHeaders::createKey(PipesHeaders::PROCESS_ID)                   => 'proc',
            PipesHeaders::createKey(PipesHeaders::NODE_ID)                      => 'node',
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)                  => 'topo',
        ];
        $dto     = new MessageDto('data', $headers);

        $manager->saveDocument($dto, ['audit1', 'audit2']);
        /** @var LongRunningNodeData $doc */
        $docs = $this->dm->getRepository(LongRunningNodeData::class)->findAll();
        $doc  = reset($docs);
        self::assertEquals(['audit1', 'audit2'], $doc->getAuditLogs());
        self::assertEquals('proc', $doc->getProcessId());
        self::assertEquals('parent', $doc->getParentProcess());
        self::assertEquals($headers, $doc->getHeaders());
        self::assertEquals('node', $doc->getNodeId());
        self::assertEquals('topo', $doc->getTopologyId());

        $headers[PipesHeaders::createKey(LongRunningNodeData::DOCUMENT_ID_HEADER)] = $doc->getid();
        $headers[PipesHeaders::createKey(PipesHeaders::NODE_ID)]                   = 'newNode';
        $dto                                                                       = new MessageDto('data2', $headers);
        $manager->saveDocument($dto, ['audit3']);

        $this->dm->clear();
        /** @var LongRunningNodeData $doc */
        $docs = $this->dm->getRepository(LongRunningNodeData::class)->findAll();
        self::assertEquals(1, count($docs));
        $doc = reset($docs);
        self::assertEquals(['audit1', 'audit2', 'audit3'], $doc->getAuditLogs());
        self::assertEquals('data2', $doc->getData());
        self::assertEquals('proc', $doc->getProcessId());
        self::assertEquals('newNode', $doc->getNodeId());
        self::assertEquals('topo', $doc->getTopologyId());

        self::assertNotEmpty($manager->getDocument('topo', 'newNode'));
        self::assertNotEmpty($manager->getDocument('topo', 'newNode', 'proc'));

        $this->expectException(LongRunningNodeException::class);
        $this->expectExceptionCode(LongRunningNodeException::LONG_RUNNING_DOCUMENT_NOT_FOUND);
        $manager->getDocument('topo', 'node', 'proc');
    }

}