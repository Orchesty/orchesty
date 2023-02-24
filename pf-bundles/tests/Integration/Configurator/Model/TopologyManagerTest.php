<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Model;

use Exception;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto;
use Hanaboso\PipesFramework\Utils\Dto\Schema;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyManagerTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Model
 */
final class TopologyManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var TopologyManager
     */
    private TopologyManager $manager;

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createTopology
     *
     * @throws Exception
     */
    public function testCreateTopologyWithSameName(): void
    {
        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS);
        self::assertEquals(1, $this->manager->createTopology(['name' => 'Topology'])->getVersion());
        $this->manager->createTopology(['name' => 'Topology'])->getVersion();
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setTopologyData
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkTopologyName
     *
     * @throws Exception
     */
    public function testUpdateUnpublishedTopologyWithName(): void
    {
        $topology = $this->manager->createTopology(['name' => 'Topology']);
        $this->manager->updateTopology($topology, ['name' => 'Another Topology']);

        $this->dm->clear();
        $topologies = $this->dm->getRepository(Topology::class)->findBy(['name' => 'another-topology']);
        self::assertEquals(1, count($topologies));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::normalizeName
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkTopologyName
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setTopologyData
     *
     * @throws Exception
     */
    public function testUpdatePublishedTopologyWithName(): void
    {
        $topology = $this->manager->createTopology(['name' => 'Topology']);
        $topology->setVisibility(TopologyStatusEnum::PUBLIC->value);
        $this->dm->flush();

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_CANNOT_CHANGE_NAME);

        $this->manager->updateTopology($topology, ['name' => 'Another Topology']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::normalizeName
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setTopologyData
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkTopologyName
     *
     * @throws Exception
     */
    public function testCheckTopologyNameUnPublished(): void
    {

        $this->manager->createTopology(['name' => 'Another Topology']);
        $topology = $this->manager->createTopology(['name' => 'Topology']);
        self::assertEquals(1, $topology->getVersion());

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS);
        $this->manager->updateTopology($topology, ['name' => 'Another Topology']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::normalizeName
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setTopologyData
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkTopologyName
     *
     * @throws Exception
     */
    public function testUpdateTopology(): void
    {
        $top = new Topology();
        $top
            ->setVisibility(TopologyStatusEnum::DRAFT->value)
            ->setDescr('asd')
            ->setName('asdd')
            ->setBpmn(['bpmn'])
            ->setRawBpmn('bpmn')
            ->setEnabled(TRUE);

        $this->dm->persist($top);

        $expt = [
            'name'        => 'name',
            'description' => 'desc',
            'bpmn'        => 'fgdgfd',
            'enabled'     => FALSE,
        ];

        $this->manager->updateTopology($top, $expt);
        $this->dm->clear();
        /** @var Topology $top */
        $top = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $top->getId()]);
        self::assertEquals('name', $top->getName());
        self::assertEquals('desc', $top->getDescr());
        self::assertEquals(['bpmn'], $top->getBpmn());
        self::assertEquals('bpmn', $top->getRawBpmn());
        self::assertFalse($top->isEnabled());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkTopologySchemaIsSame
     *
     * @throws Exception
     */
    public function testCheckTopologyIsSame(): void
    {
        $schema = $this->getSchema();

        $top = new Topology();
        $top
            ->setVisibility(TopologyStatusEnum::DRAFT->value)
            ->setDescr('asd')
            ->setName('asdd')
            ->setEnabled(TRUE);

        $this->dm->persist($top);

        $this->manager->saveTopologySchema($top, '', $schema);
        self::assertEquals(TRUE, $this->manager->checkTopologySchemaIsSame($top, $schema));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::publishTopology
     *
     * @throws Exception
     */
    public function testPublishTopology(): void
    {
        $top = new Topology();
        $top->setName('asd')->setVisibility(TopologyStatusEnum::DRAFT->value);

        $this->dm->persist($top);

        $node = new Node();
        $node
            ->setName('abc')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setTopology($top->getId());

        $this->dm->persist($node);
        $this->dm->flush();

        /** @var Topology $res */
        $res = $this->manager->publishTopology($top);
        self::assertEquals(TopologyStatusEnum::PUBLIC->value, $res->getVisibility());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::publishTopology
     *
     * @throws Exception
     */
    public function testPublishTopologyNoNodes(): void
    {
        $top = new Topology();
        $top->setName('asd')->setVisibility(TopologyStatusEnum::DRAFT->value);

        $this->dm->persist($top);
        $this->dm->flush();

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_HAS_NO_NODES);

        $this->manager->publishTopology($top);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::makePatchRequestForCron
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopologyShallow
     *
     * @throws Exception
     */
    public function testCloneTopology(): void
    {
        $top = new Topology();
        $top
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(FALSE)
            ->setDescr('desc')
            ->setBpmn(['asd'])
            ->setRawBpmn('asd');

        $this->dm->persist($top);
        $this->dm->flush();

        /**
         * 1 -> 2 -> 3
         *        -> 4 -> 5
         */

        $node5 = new Node();
        $node5
            ->setName('node5')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node5')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE);
        $this->dm->persist($node5);

        $node4 = new Node();
        $node4
            ->setName('node4')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node4')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node5));
        $this->dm->persist($node4);

        $node3 = new Node();
        $node3
            ->setName('node3')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node3')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE);
        $this->dm->persist($node3);

        $node2 = new Node();
        $node2
            ->setName('node2')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node2')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node3))
            ->addNext(EmbedNode::from($node4));
        $this->dm->persist($node2);

        $node1 = new Node();
        $node1
            ->setName('node1')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node1')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node2));
        $this->dm->persist($node1);

        $this->dm->flush();
        $this->dm->clear();

        /** @var Topology $res */
        $res = $this->manager->cloneTopology($top);

        self::assertEquals($top->getName(), $res->getName());
        self::assertEquals($top->getVersion() + 1, $res->getVersion());
        self::assertEquals($top->getDescr(), $res->getDescr());
        self::assertEquals(TopologyStatusEnum::DRAFT->value, $res->getVisibility());
        self::assertEquals($top->isEnabled(), $res->isEnabled());
        self::assertEquals($top->getBpmn(), $res->getBpmn());
        self::assertEquals($top->getRawBpmn(), $res->getRawBpmn());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $res->getId()]);
        self::assertCount(5, $nodes);

        foreach ($nodes as $node) {
            if ($node->getName() == 'node1') {
                self::assertNodeAfterClone($node1, $node, $res, 1);
            } else if ($node->getName() == 'node2') {
                self::assertNodeAfterClone($node2, $node, $res, 2);
            } else if ($node->getName() == 'node3') {
                self::assertNodeAfterClone($node3, $node, $res, 0);
            } else if ($node->getName() == 'node4') {
                self::assertNodeAfterClone($node4, $node, $res, 1);
            } else if ($node->getName() == 'node5') {
                self::assertNodeAfterClone($node5, $node, $res, 0);
            }
        }
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::makePatchRequestForCron
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopologyShallow
     *
     * @throws Exception
     */
    public function testCloneTopologyWithoutBpmn(): void
    {
        $top = new Topology();
        $top
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(FALSE)
            ->setDescr('desc');

        $this->dm->persist($top);
        $this->dm->flush();
        $this->dm->clear();

        /** @var Topology $res */
        $res = $this->manager->cloneTopology($top);

        self::assertEquals($top->getName(), $res->getName());
        self::assertEquals($top->getVersion() + 1, $res->getVersion());
        self::assertEquals($top->getDescr(), $res->getDescr());
        self::assertEquals(TopologyStatusEnum::DRAFT->value, $res->getVisibility());
        self::assertEquals($top->isEnabled(), $res->isEnabled());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopologyShallow
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::generateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::removeNodesByTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getNodeBySchemaId
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createNode
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getSequences
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getNodes
     *
     * @throws Exception
     */
    public function testSaveTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        $result = $this->manager->saveTopologySchema($topology, '', $this->getSchema());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);

        self::assertEquals($topology->getId(), $result->getId());
        self::assertEquals(7, count($nodes));

        self::assertNodesFromSchemaFile($nodes);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopologyShallow
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::generateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::removeNodesByTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getNodeBySchemaId
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createNode
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getSequences
     * @covers \Hanaboso\PipesFramework\Utils\Dto\Schema::getNodes
     *
     * @throws Exception
     */
    public function testReSaveTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setContentHash('123');
        $this->pfd($topology);

        $node = (new Node())
            ->setName('deleted')
            ->setTopology($topology->getId());
        $this->pfd($node);

        $result = $this->manager->saveTopologySchema($topology, '', $this->getSchema());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);

        self::assertEquals($topology->getId(), $result->getId());
        self::assertEquals(7, count($nodes));

        self::assertNodesFromSchemaFile($nodes);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopologyShallow
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::generateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::removeNodesByTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getNodeBySchemaId
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createNode
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaWithClone(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setContentHash('abcd');

        $this->dm->persist($topology);

        $node2 = new Node();
        $node2
            ->setName('node2')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node2')
            ->setTopology($topology->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE);
        $this->dm->persist($node2);

        $node1 = new Node();
        $node1
            ->setName('node1')
            ->setType(TypeEnum::CONNECTOR->value)
            ->setSchemaId('schema-node1')
            ->setTopology($topology->getId())
            ->setHandler(HandlerEnum::EVENT->value)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node2));
        $this->dm->persist($node1);

        $this->dm->flush();
        $this->dm->clear();

        $result = $this->manager->saveTopologySchema($topology, '', $this->getSchema());

        self::assertNotEquals($topology->getId(), $result->getId());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $result->getId()]);

        self::assertNotEquals($topology->getId(), $result->getId()); // because it is cloned
        self::assertEquals(7, count($nodes));

        self::assertNodesFromSchemaFile($nodes);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopologyShallow
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::generateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::removeNodesByTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getNodeBySchemaId
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createNode
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaUpdateNodes(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->dm->persist($topology);
        $this->dm->flush();
        $this->dm->clear();

        $result1 = $this->manager->saveTopologySchema($topology, '', $this->getSchema());
        $result2 = $this->manager->saveTopologySchema($result1, '', $this->getSchema('schema-update.json'));

        /** @var Node[] $nodes1 */
        $nodes1 = $this->dm->getRepository(Node::class)->findBy(['topology' => $result1->getId()]);

        /** @var Node[] $nodes2 */
        $nodes2 = $this->dm->getRepository(Node::class)->findBy(['topology' => $result2->getId()]);

        self::assertEquals($topology->getId(), $result2->getId()); // it is only updated
        self::assertEquals(7, count($nodes2));

        self::assertEquals($nodes1[0]->getId(), $nodes2[0]->getId());
        self::assertEquals('Start Event', $nodes2[0]->getName());
        self::assertEquals(TypeEnum::CUSTOM->value, $nodes2[0]->getType());
        self::assertEquals(HandlerEnum::EVENT->value, $nodes2[0]->getHandler());

        self::assertEquals($nodes1[1]->getId(), $nodes2[1]->getId());
        self::assertEquals('Connector DEF', $nodes2[1]->getName());
        self::assertEquals(TypeEnum::CONNECTOR->value, $nodes2[1]->getType());
        self::assertEquals(HandlerEnum::ACTION->value, $nodes2[1]->getHandler());

        self::assertEquals($nodes1[2]->getId(), $nodes2[2]->getId());
        self::assertEquals('Mapper XYZ', $nodes2[2]->getName());
        self::assertEquals(TypeEnum::MAPPER->value, $nodes2[2]->getType());
        self::assertEquals(HandlerEnum::ACTION->value, $nodes2[2]->getHandler());

        self::assertEquals($nodes1[3]->getId(), $nodes2[3]->getId());
        self::assertEquals('Parser ABC', $nodes2[3]->getName());
        self::assertEquals(TypeEnum::XML_PARSER->value, $nodes2[3]->getType());
        self::assertEquals(HandlerEnum::ACTION->value, $nodes2[3]->getHandler());
        self::assertEquals(1, count($nodes2[3]->getNext()));
        self::assertEquals('Connector DEF', $nodes2[3]->getNext()[0]->getName());

        self::assertEquals($nodes1[4]->getId(), $nodes2[4]->getId());
        self::assertEquals('Splitter SPI', $nodes2[4]->getName());
        self::assertEquals(TypeEnum::SPLITTER->value, $nodes2[4]->getType());
        self::assertEquals(HandlerEnum::ACTION->value, $nodes2[4]->getHandler());

        self::assertEquals($nodes1[5]->getId(), $nodes2[5]->getId());
        self::assertEquals('Event 1', $nodes2[5]->getName());
        self::assertEquals(TypeEnum::CRON->value, $nodes2[5]->getType());
        self::assertEquals(HandlerEnum::EVENT->value, $nodes2[5]->getHandler());
        self::assertEquals(1, count($nodes2[5]->getNext()));
        self::assertEquals('*/2 2 * * *', $nodes2[5]->getCron());
        self::assertEquals('Parser ABC', $nodes2[5]->getNext()[0]->getName());

        self::assertEquals($nodes1[6]->getId(), $nodes2[6]->getId());
        self::assertEquals('Event 2', $nodes2[6]->getName());
        self::assertEquals(TypeEnum::WEBHOOK->value, $nodes2[6]->getType());
        self::assertEquals(HandlerEnum::EVENT->value, $nodes2[6]->getHandler());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopologyShallow
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::generateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::removeNodesByTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getNodeBySchemaId
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createNode
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaNameNotFound(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND);

        $schema = $this->getSchema();
        unset($schema['bpmn:process']['bpmn:startEvent']['@name']);
        $this->manager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopologyShallow
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::generateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::removeNodesByTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getNodeBySchemaId
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createNode
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaTypeNotExist(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST);

        $schema                                                        = $this->getSchema();
        $schema['bpmn:process']['bpmn:startEvent']['@pipes:pipesType'] = 'Unknown';
        $this->manager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::cloneTopologyShallow
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::generateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::removeNodesByTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getNodeBySchemaId
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setNodeAttributes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::createNode
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaCronNotValid(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NODE_CRON_NOT_VALID);

        $schema                                                     = $this->getSchema();
        $schema['bpmn:process']['bpmn:event'][0]['@pipes:cronTime'] = 'Unknown';
        $this->manager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::deleteTopology
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::removeNodesByTopology
     *
     * @throws Exception
     */
    public function testDeleteTopology(): void
    {
        $node  = new Node();
        $node2 = new Node();
        $top   = new Topology();
        $top
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::DRAFT->value);
        $this->pfd($top);
        $node->setName('node')->setType(TypeEnum::MAPPER->value)->setTopology($top->getId());
        $node2->setName('node')->setType(TypeEnum::CRON->value)->setTopology($top->getId());
        $this->pfd($node);
        $this->pfd($node2);

        $this->manager->deleteTopology($top);
        $this->dm->clear();
        self::assertEmpty(
            $this->dm->getRepository(Topology::class)->findBy(
                [
                    'id'      => $top->getId(),
                    'deleted' => FALSE,
                ],
            ),
        );
        self::assertEmpty($this->dm->getRepository(Node::class)->findBy(['id' => $node->getId(), 'deleted' => FALSE]));
        self::assertEmpty($this->dm->getRepository(Node::class)->findBy(['id' => $node2->getId(), 'deleted' => FALSE]));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getCronTopologies
     *
     * @throws Exception
     */
    public function testGetCronTopologies(): void
    {
        $tp  = (new Topology())->setName('Topology')->setVersion(1)->setEnabled(TRUE);
        $tp2 = (new Topology())->setName('Topology')->setVersion(2)->setEnabled(FALSE);
        $tp3 = (new Topology())->setName('Topology')->setVersion(2)->setEnabled(FALSE)->setDeleted(TRUE);
        $this->dm->persist($tp);
        $this->dm->persist($tp2);
        $this->dm->persist($tp3);
        $this->dm->flush();

        $cronManager = self::createMock(CronManager::class);
        $cronManager->method('getAll')->willReturn(
            new ResponseDto(
                200,
                'OK',
                sprintf(
                    '[{"topology":"%s", "node":"Node", "time":"*/1 * * * *"}, {"topology":"%s", "node":"Node", "time":"*/1 * * * *"}]',
                    $tp->getId(),
                    $tp2->getId(),
                ),
                [],
            ),
        );

        $this->setProperty($this->manager, 'cronManager', $cronManager);
        $topologies = $this->manager->getCronTopologies();

        self::assertEquals(
            [
                [
                    'topology' => [
                        'id'      => $topologies[0]['topology']['id'],
                        'name'    => 'Topology',
                        'status'  => TRUE,
                        'version' => 1,
                    ],
                    'node'     => [
                        'name' => 'Node',
                    ],
                    'time'     => '*/1 * * * *',
                ], [
                    'topology' => [
                        'id'      => $topologies[1]['topology']['id'],
                        'name'    => 'Topology',
                        'status'  => FALSE,
                        'version' => 2,
                    ],
                    'node'     => [
                        'name' => 'Node',
                    ],
                    'time'     => '*/1 * * * *',
                ],
            ],
            $topologies,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getCronTopologies
     *
     * @throws Exception
     */
    public function testGetCronTopologiesRes(): void
    {
        $tp  = (new Topology())->setName('Topology')->setVersion(1)->setEnabled(TRUE);
        $tp2 = (new Topology())->setName('Topology')->setVersion(2)->setEnabled(TRUE);
        $this->dm->persist($tp);
        $this->dm->persist($tp2);
        $this->dm->flush();

        $cronManager = self::createMock(CronManager::class);
        $cronManager->method('getAll')->willReturn(
            new ResponseDto(
                200,
                'OK',
                sprintf(
                    '[{"topology":"%s", "node":"Node", "time":"*/1 * * * *"}, {"topology":"%s", "node":"Node", "time":"*/1 * * * *"}]',
                    $tp->getId(),
                    $tp2->getId(),
                ),
                [],
            ),
        );
        $this->setProperty($this->manager, 'cronManager', $cronManager);

        $topologies = $this->manager->getCronTopologies();

        self::assertEquals(
            [
                [
                    'topology' => [
                        'id'      => $tp2->getId(),
                        'name'    => 'Topology',
                        'status'  => TRUE,
                        'version' => 2,
                    ],
                    'node'     => ['name' => 'Node'],
                    'time'     => '*/1 * * * *',
                ],
                [
                    'topology' => [
                        'id'      => $tp->getId(),
                        'name'    => 'Topology',
                        'status'  => TRUE,
                        'version' => 1,
                    ],
                    'node'     => ['name' => 'Node'],
                    'time'     => '*/1 * * * *',
                ],
            ],
            $topologies,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getCronTopologies
     *
     * @throws Exception
     */
    public function testGetCronTopologiesNotFound(): void
    {
        $cronManager = self::createMock(CronManager::class);
        $cronManager->method('getAll')->willReturn(
            new ResponseDto(200, 'OK', '[{"topology":"Topology", "node":"Node", "time":"*/1 * * * *"}]', []),
        );

        $this->setProperty($this->manager, 'cronManager', $cronManager);

        self::assertCount(0, $this->manager->getCronTopologies());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto
     * @covers \Hanaboso\PipesFramework\Database\Document\Node::setName
     * @covers \Hanaboso\PipesFramework\Database\Document\Node::setSystemConfigs
     * @covers \Hanaboso\PipesFramework\Database\Document\Node::getName
     * @covers \Hanaboso\PipesFramework\Database\Document\Node::getSystemConfigs
     *
     * @throws Exception
     */
    public function testSystemConfig(): void
    {
        $dto  = new SystemConfigDto('host1', 'bridge1', 2, TRUE, 2, 2);
        $node = new Node();
        $node
            ->setName('node10')
            ->setSystemConfigs($dto);

        $this->dm->persist($node);
        $this->dm->flush();
        $foundNode = $this->dm->getRepository(Node::class)->findBy(['name' => 'node10']);

        self::assertEquals('node10', $foundNode[0]->getName());
        self::assertIsObject($foundNode[0]->getSystemConfigs());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::unPublishTopology
     *
     * @throws Exception
     */
    public function testUnPublishTopology(): void
    {
        $topology = $this->manager->unPublishTopology(new Topology());

        self::assertEquals(TopologyStatusEnum::DRAFT->value, $topology->getVisibility());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::updateNodes
     *
     * @throws Exception
     */
    public function testUpdateNodes(): void
    {
        $node = (new Node())->setType('api');
        $this->pfd($node);

        $topology = new Topology();
        $this->pfd($topology);

        $this->invokeMethod(
            $this->manager,
            'updateNodes',
            [
                $topology,
                (new Schema())
                    ->addNode('1', new NodeSchemaDto('handler', $node->getId(), 'api', new SystemConfigDto(), 'name')),
            ],
        );

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::checkNodeAttributes
     *
     * @throws Exception
     */
    public function testCheckNodeAttributes(): void
    {
        $nodeSchema = new NodeSchemaDto('handler', '1', '', new SystemConfigDto(), 'name');

        self::expectException(TopologyException::class);
        $this->invokeMethod($this->manager, 'checkNodeAttributes', [$nodeSchema]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::getNodeBySchemaId
     *
     * @throws Exception
     */
    public function testGetNodeBySchemaId(): void
    {
        $topology = new Topology();
        $this->pfd($topology);

        self::expectException(NodeException::class);
        $this->invokeMethod($this->manager, 'getNodeBySchemaId', [$topology, '1']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::setTopologyData
     *
     * @throws Exception
     */
    public function testSetTopologyData(): void
    {
        $topology = new Topology();
        $this->pfd($topology);

        $this->invokeMethod(
            $this->manager,
            'setTopologyData',
            [
                $topology,
                [
                    'name'     => 'name',
                    'desc'     => 'desc',
                    'enabled'  => TRUE,
                    'category' => 'category',
                ],
            ],
        );

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::getContainer()->get('hbpf.configurator.manager.topology');

        $cronManager = self::getContainer()->get('hbpf.cron.manager');
        $this->setProperty($cronManager, 'curlManager', self::createMock(CurlManagerInterface::class));
        $this->setProperty($this->manager, 'cronManager', $cronManager);
    }

    /**
     * @param string $name
     *
     * @return mixed[]
     *
     * @throws Exception
     */
    private function getSchema(string $name = 'schema.json'): array
    {
        return Json::decode(File::getContent(sprintf('%s/data/%s', __DIR__, $name)));
    }

    /**
     * @param Node[] $nodes
     */
    private function assertNodesFromSchemaFile(array $nodes): void
    {
        self::assertEquals('Start Event', $nodes[0]->getName());
        self::assertEquals(TypeEnum::CUSTOM->value, $nodes[0]->getType());
        self::assertEquals(HandlerEnum::EVENT->value, $nodes[0]->getHandler());

        self::assertEquals('Connector DEF', $nodes[1]->getName());
        self::assertEquals(TypeEnum::CONNECTOR->value, $nodes[1]->getType());
        self::assertEquals(HandlerEnum::ACTION->value, $nodes[1]->getHandler());

        self::assertEquals('Mapper XYZ', $nodes[2]->getName());
        self::assertEquals(TypeEnum::MAPPER->value, $nodes[2]->getType());
        self::assertEquals(HandlerEnum::ACTION->value, $nodes[2]->getHandler());

        self::assertEquals('Parser ABC', $nodes[3]->getName());
        self::assertEquals(TypeEnum::XML_PARSER->value, $nodes[3]->getType());
        self::assertEquals(HandlerEnum::ACTION->value, $nodes[3]->getHandler());
        self::assertEquals(1, count($nodes[3]->getNext()));
        self::assertEquals('Connector DEF', $nodes[3]->getNext()[0]->getName());

        self::assertEquals('Splitter SPI', $nodes[4]->getName());
        self::assertEquals(TypeEnum::SPLITTER->value, $nodes[4]->getType());
        self::assertEquals(HandlerEnum::ACTION->value, $nodes[4]->getHandler());

        self::assertEquals('Event 1', $nodes[5]->getName());
        self::assertEquals(TypeEnum::CRON->value, $nodes[5]->getType());
        self::assertEquals(HandlerEnum::EVENT->value, $nodes[5]->getHandler());
        self::assertEquals(1, count($nodes[5]->getNext()));
        self::assertEquals('*/2 * * * *', $nodes[5]->getCron());
        self::assertEquals('Parser ABC', $nodes[5]->getNext()[0]->getName());

        self::assertEquals('Event 2', $nodes[6]->getName());
        self::assertEquals(TypeEnum::WEBHOOK->value, $nodes[6]->getType());
        self::assertEquals(HandlerEnum::EVENT->value, $nodes[6]->getHandler());
    }

    /**
     * @param Node     $expected
     * @param Node     $actual
     * @param Topology $topology
     * @param int      $nextCount
     *
     * @throws Exception
     */
    private function assertNodeAfterClone(Node $expected, Node $actual, Topology $topology, int $nextCount): void
    {
        self::assertFalse($expected->getId() == $actual->getId());
        self::assertEquals($expected->getName(), $actual->getName());
        self::assertEquals($expected->getType(), $actual->getType());
        self::assertEquals($topology->getId(), $actual->getTopology());
        self::assertEquals($expected->getHandler(), $actual->getHandler());
        self::assertEquals($expected->isEnabled(), $actual->isEnabled());

        // next
        self::assertEquals($nextCount, count($expected->getNext()));
        self::assertEquals($nextCount, count($actual->getNext()));

        /** @var EmbedNode[] $expNext */
        $expNext = $expected->getNext();
        /** @var EmbedNode[] $actNext */
        $actNext = $actual->getNext();

        if ($nextCount == 1) {
            self::assertFalse($expNext[0]->getId() == $actNext[0]->getId());
            self::assertEquals($expNext[0]->getName(), $actNext[0]->getName());
        } else if ($nextCount == 2) {
            self::assertFalse($expNext[0]->getId() == $actNext[0]->getId());
            self::assertEquals($expNext[0]->getName(), $actNext[0]->getName());
            self::assertEquals($expNext[1]->getName(), $actNext[1]->getName());
        }
    }

}
