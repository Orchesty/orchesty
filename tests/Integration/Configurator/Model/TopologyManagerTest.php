<?php declare(strict_types=1);

namespace Tests\Integration\Configurator\Model;

use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologyManagerTest
 *
 * @package Tests\Integration\Configurator\Model
 */
final class TopologyManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws TopologyException
     */
    public function testCreateTopologyWithSameName(): void
    {
        $manager = $this->container->get('hbpf.configurator.manager.topology');

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS);
        self::assertEquals(1, $manager->createTopology(['name' => 'Topology'])->getVersion());
        $manager->createTopology(['name' => 'Topology'])->getVersion();
    }

    /**
     * @throws TopologyException
     */
    public function testUpdateUnpublishedTopologyWithName(): void
    {
        $manager  = $this->container->get('hbpf.configurator.manager.topology');
        $topology = $manager->createTopology(['name' => 'Topology']);
        $manager->updateTopology($topology, ['name' => 'Another Topology']);

        $this->dm->clear();
        $topologies = $this->dm->getRepository(Topology::class)->findBy(['name' => 'Another Topology']);
        self::assertEquals(1, count($topologies));
    }

    /**
     * @throws TopologyException
     */
    public function testUpdatePublishedTopologyWithName(): void
    {
        $manager  = $this->container->get('hbpf.configurator.manager.topology');
        $topology = $manager->createTopology(['name' => 'Topology']);
        $topology->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->flush();

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_CANNOT_CHANGE_NAME);

        $manager->updateTopology($topology, ['name' => 'Another Topology']);
    }

    /**
     * @throws TopologyException
     */
    public function testCheckTopologyNameUnPublished(): void
    {
        $manager = $this->container->get('hbpf.configurator.manager.topology');

        $manager->createTopology(['name' => 'Another Topology']);
        $topology = $manager->createTopology(['name' => 'Topology']);
        self::assertEquals(1, $topology->getVersion());

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_NAME_ALREADY_EXISTS);
        $manager->updateTopology($topology, ['name' => 'Another Topology']);
    }

    /**
     * @throws TopologyException
     */
    public function testUpdateTopology(): void
    {
        $top = new Topology();
        $top
            ->setVisibility(TopologyStatusEnum::DRAFT)
            ->setDescr('asd')
            ->setName('asdd')
            ->setBpmn(['bpmn'])
            ->setRawBpmn('bpmn')
            ->setEnabled(TRUE);

        $this->dm->persist($top);

        $expt = [
            'name'    => 'name',
            'descr'   => 'desc',
            'bpmn'    => 'fgdgfd',
            'enabled' => FALSE,
        ];

        $this->container->get('hbpf.configurator.manager.topology')->updateTopology($top, $expt);
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
     * @throws TopologyException
     * @throws NodeException
     */
    public function testPublishTopology(): void
    {
        $top = new Topology();
        $top->setName('asd')->setVisibility(TopologyStatusEnum::DRAFT);

        $this->dm->persist($top);

        $node = new Node();
        $node
            ->setName('abc')
            ->setType(TypeEnum::CONNECTOR)
            ->setTopology($top->getId());

        $this->dm->persist($node);
        $this->dm->flush();

        /** @var Topology $res */
        $res = $this->container->get('hbpf.configurator.manager.topology')->publishTopology($top);
        self::assertEquals(TopologyStatusEnum::PUBLIC, $res->getVisibility());
    }

    /**
     * @throws TopologyException
     */
    public function testPublishTopologyNoNodes(): void
    {
        $top = new Topology();
        $top->setName('asd')->setVisibility(TopologyStatusEnum::DRAFT);

        $this->dm->persist($top);
        $this->dm->flush();

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_HAS_NO_NODES);

        $this->container->get('hbpf.configurator.manager.topology')->publishTopology($top);
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testCloneTopology(): void
    {
        $top = new Topology();
        $top
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC)
            ->setEnabled(FALSE)
            ->setDescr('desc')
            ->setBpmn(['asd'])
            ->setRawBpmn('asd');

        $this->dm->persist($top);
        $this->dm->flush($top);

        /**
         * 1 -> 2 -> 3
         *        -> 4 -> 5
         */

        $node5 = new Node();
        $node5
            ->setName('node5')
            ->setType(TypeEnum::CONNECTOR)
            ->setSchemaId('schema-node5')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT)
            ->setEnabled(TRUE);
        $this->dm->persist($node5);

        $node4 = new Node();
        $node4
            ->setName('node4')
            ->setType(TypeEnum::CONNECTOR)
            ->setSchemaId('schema-node4')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node5));
        $this->dm->persist($node4);

        $node3 = new Node();
        $node3
            ->setName('node3')
            ->setType(TypeEnum::CONNECTOR)
            ->setSchemaId('schema-node3')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT)
            ->setEnabled(TRUE);
        $this->dm->persist($node3);

        $node2 = new Node();
        $node2
            ->setName('node2')
            ->setType(TypeEnum::CONNECTOR)
            ->setSchemaId('schema-node2')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node3))
            ->addNext(EmbedNode::from($node4));
        $this->dm->persist($node2);

        $node1 = new Node();
        $node1
            ->setName('node1')
            ->setType(TypeEnum::CONNECTOR)
            ->setSchemaId('schema-node1')
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node2));
        $this->dm->persist($node1);

        $this->dm->flush();
        $this->dm->clear();

        /** @var Topology $res */
        $res = $this->container->get('hbpf.configurator.manager.topology')->cloneTopology($top);

        self::assertEquals($top->getName(), $res->getName());
        self::assertEquals($top->getVersion() + 1, $res->getVersion());
        self::assertEquals($top->getDescr(), $res->getDescr());
        self::assertEquals(TopologyStatusEnum::DRAFT, $res->getVisibility());
        self::assertEquals($top->isEnabled(), $res->isEnabled());
        self::assertEquals($top->getBpmn(), $res->getBpmn());
        self::assertEquals($top->getRawBpmn(), $res->getRawBpmn());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $res->getId()]);
        self::assertCount(5, $nodes);

        foreach ($nodes as $node) {
            if ($node->getName() == 'node1') {
                $this->assertNodeAfterClone($node1, $node, $res, 1);
            } elseif ($node->getName() == 'node2') {
                $this->assertNodeAfterClone($node2, $node, $res, 2);
            } elseif ($node->getName() == 'node3') {
                $this->assertNodeAfterClone($node3, $node, $res, 0);
            } elseif ($node->getName() == 'node4') {
                $this->assertNodeAfterClone($node4, $node, $res, 1);
            } elseif ($node->getName() == 'node5') {
                $this->assertNodeAfterClone($node5, $node, $res, 0);
            }
        }
    }

    /**
     * @param Node     $expected
     * @param Node     $actual
     * @param Topology $topology
     * @param int      $nextCount
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
        self::assertEquals($nextCount, $expected->getNext()->count());
        self::assertEquals($nextCount, $actual->getNext()->count());

        /** @var EmbedNode[] $expNext */
        /** @var EmbedNode[] $actNext */
        $expNext = $expected->getNext()->toArray();
        $actNext = $actual->getNext()->toArray();

        if ($nextCount == 1) {
            self::assertFalse($expNext[0]->getId() == $actNext[0]->getId());
            self::assertEquals($expNext[0]->getName(), $actNext[0]->getName());
        } elseif ($nextCount == 2) {
            self::assertFalse($expNext[0]->getId() == $actNext[0]->getId());
            self::assertEquals($expNext[0]->getName(), $actNext[0]->getName());
            self::assertEquals($expNext[1]->getName(), $actNext[1]->getName());
        }
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testCloneTopologyWithoutBpmn(): void
    {
        $top = new Topology();
        $top
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC)
            ->setEnabled(FALSE)
            ->setDescr('desc');

        $this->dm->persist($top);
        $this->dm->flush($top);
        $this->dm->clear();

        /** @var Topology $res */
        $res = $this->container->get('hbpf.configurator.manager.topology')->cloneTopology($top);

        self::assertEquals($top->getName(), $res->getName());
        self::assertEquals($top->getVersion() + 1, $res->getVersion());
        self::assertEquals($top->getDescr(), $res->getDescr());
        self::assertEquals(TopologyStatusEnum::DRAFT, $res->getVisibility());
        self::assertEquals($top->isEnabled(), $res->isEnabled());
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testSaveTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->persistAndFlush($topology);

        $topologyManager = $this->container->get('hbpf.configurator.manager.topology');
        $result          = $topologyManager->saveTopologySchema($topology, '', $this->getSchema('schema.json'));

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);

        self::assertEquals($topology->getId(), $result->getId());
        self::assertEquals(7, count($nodes));

        $this->assertNodesFromSchemaFile($nodes);
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testSaveTopologySchemaWithClone(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setVisibility(TopologyStatusEnum::PUBLIC)
            ->setContentHash('abcd');

        $this->dm->persist($topology);

        $node2 = new Node();
        $node2
            ->setName('node2')
            ->setType(TypeEnum::CONNECTOR)
            ->setSchemaId('schema-node2')
            ->setTopology($topology->getId())
            ->setHandler(HandlerEnum::EVENT)
            ->setEnabled(TRUE);
        $this->dm->persist($node2);

        $node1 = new Node();
        $node1
            ->setName('node1')
            ->setType(TypeEnum::CONNECTOR)
            ->setSchemaId('schema-node1')
            ->setTopology($topology->getId())
            ->setHandler(HandlerEnum::EVENT)
            ->setEnabled(TRUE)
            ->addNext(EmbedNode::from($node2));
        $this->dm->persist($node1);

        $this->dm->flush();
        $this->dm->clear();

        $topologyManager = $this->container->get('hbpf.configurator.manager.topology');
        $result          = $topologyManager->saveTopologySchema($topology, '', $this->getSchema('schema.json'));

        self::assertNotEquals($topology->getId(), $result->getId());

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $result->getId()]);

        self::assertNotEquals($topology->getId(), $result->getId()); // because it is cloned
        self::assertEquals(7, count($nodes));

        $this->assertNodesFromSchemaFile($nodes);
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testSaveTopologySchemaUpdateNodes(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->dm->persist($topology);
        $this->dm->flush();
        $this->dm->clear();

        $topologyManager = $this->container->get('hbpf.configurator.manager.topology');
        $result1         = $topologyManager->saveTopologySchema($topology, '', $this->getSchema('schema.json'));
        $result2         = $topologyManager->saveTopologySchema($result1, '', $this->getSchema('schema-update.json'));

        /** @var Node[] $nodes1 */
        $nodes1 = $this->dm->getRepository(Node::class)->findBy(['topology' => $result1->getId()]);

        /** @var Node[] $nodes2 */
        $nodes2 = $this->dm->getRepository(Node::class)->findBy(['topology' => $result2->getId()]);

        self::assertEquals($topology->getId(), $result2->getId()); // it is only updated
        self::assertEquals(7, count($nodes2));

        self::assertEquals($nodes1[0]->getId(), $nodes2[0]->getId());
        self::assertEquals('Start Event', $nodes2[0]->getName());
        self::assertEquals(TypeEnum::CUSTOM, $nodes2[0]->getType());
        self::assertEquals(HandlerEnum::EVENT, $nodes2[0]->getHandler());

        self::assertEquals($nodes1[1]->getId(), $nodes2[1]->getId());
        self::assertEquals('Connector DEF', $nodes2[1]->getName());
        self::assertEquals(TypeEnum::CONNECTOR, $nodes2[1]->getType());
        self::assertEquals(HandlerEnum::ACTION, $nodes2[1]->getHandler());

        self::assertEquals($nodes1[2]->getId(), $nodes2[2]->getId());
        self::assertEquals('Mapper XYZ', $nodes2[2]->getName());
        self::assertEquals(TypeEnum::MAPPER, $nodes2[2]->getType());
        self::assertEquals(HandlerEnum::ACTION, $nodes2[2]->getHandler());

        self::assertEquals($nodes1[3]->getId(), $nodes2[3]->getId());
        self::assertEquals('Parser ABC', $nodes2[3]->getName());
        self::assertEquals(TypeEnum::XML_PARSER, $nodes2[3]->getType());
        self::assertEquals(HandlerEnum::ACTION, $nodes2[3]->getHandler());
        self::assertEquals(1, count($nodes2[3]->getNext()));
        self::assertEquals('Connector DEF', $nodes2[3]->getNext()[0]->getName());

        self::assertEquals($nodes1[4]->getId(), $nodes2[4]->getId());
        self::assertEquals('Splitter SPI', $nodes2[4]->getName());
        self::assertEquals(TypeEnum::SPLITTER, $nodes2[4]->getType());
        self::assertEquals(HandlerEnum::ACTION, $nodes2[4]->getHandler());

        self::assertEquals($nodes1[5]->getId(), $nodes2[5]->getId());
        self::assertEquals('Event 1', $nodes2[5]->getName());
        self::assertEquals(TypeEnum::CRON, $nodes2[5]->getType());
        self::assertEquals(HandlerEnum::EVENT, $nodes2[5]->getHandler());
        self::assertEquals(1, count($nodes2[5]->getNext()));
        self::assertEquals('*/2 2 * * *', $nodes2[5]->getCron());
        self::assertEquals('Parser ABC', $nodes2[5]->getNext()[0]->getName());

        self::assertEquals($nodes1[6]->getId(), $nodes2[6]->getId());
        self::assertEquals('Event 2', $nodes2[6]->getName());
        self::assertEquals(TypeEnum::WEBHOOK, $nodes2[6]->getType());
        self::assertEquals(HandlerEnum::EVENT, $nodes2[6]->getHandler());
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testSaveTopologySchemaNameNotFound(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->persistAndFlush($topology);

        $topologyManager = $this->container->get('hbpf.configurator.manager.topology');

        $this->expectException(TopologyException::class);
        $this->expectExceptionCode(TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND);

        $schema = $this->getSchema('schema.json');
        unset($schema['bpmn:process']['bpmn:startEvent']['@name']);
        $topologyManager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testSaveTopologySchemaTypeNotFound(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->persistAndFlush($topology);

        $topologyManager = $this->container->get('hbpf.configurator.manager.topology');

        $this->expectException(TopologyException::class);
        $this->expectExceptionCode(TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND);

        $schema = $this->getSchema('schema.json');
        unset($schema['bpmn:process']['bpmn:startEvent']['@pipes:pipesType']);
        $topologyManager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testSaveTopologySchemaTypeNotExist(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->persistAndFlush($topology);

        $topologyManager = $this->container->get('hbpf.configurator.manager.topology');

        $this->expectException(TopologyException::class);
        $this->expectExceptionCode(TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST);

        $schema                                                        = $this->getSchema('schema.json');
        $schema['bpmn:process']['bpmn:startEvent']['@pipes:pipesType'] = 'Unknown';
        $topologyManager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testSaveTopologySchemaCronNotValid(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->persistAndFlush($topology);

        $topologyManager = $this->container->get('hbpf.configurator.manager.topology');

        $this->expectException(TopologyException::class);
        $this->expectExceptionCode(TopologyException::TOPOLOGY_NODE_CRON_NOT_VALID);

        $schema                                                     = $this->getSchema('schema.json');
        $schema['bpmn:process']['bpmn:event'][0]['@pipes:cronTime'] = 'Unknown';
        $topologyManager->saveTopologySchema($topology, '', $schema);
    }

    /**
     * @throws TopologyException
     * @throws NodeException
     */
    public function testDeleteTopology(): void
    {
        $manager = $this->container->get('hbpf.configurator.manager.topology');

        $node = new Node();
        $top  = new Topology();
        $top
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->persistAndFlush($top);
        $node->setName('node')->setType(TypeEnum::MAPPER)->setTopology($top->getId());
        $this->persistAndFlush($node);

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::CANNOT_DELETE_PUBLIC_TOPOLOGY);
        $manager->deleteTopology($top);

        $top->setVisibility(TopologyStatusEnum::DRAFT);
        $manager->deleteTopology($top);
        $this->dm->clear();
        self::assertNull($this->dm->getRepository(TopologyRepository::class)->find($top->getId()));
        self::assertNull($this->dm->getRepository(Node::class)->find($node->getId()));
    }

    /**
     * @param string $name
     *
     * @return array
     */
    private function getSchema(string $name = 'schema.json'): array
    {
        return json_decode(file_get_contents(sprintf('%s/data/%s', __DIR__, $name)), TRUE);
    }

    /**
     * @param Node[] $nodes
     */
    private function assertNodesFromSchemaFile(array $nodes): void
    {
        self::assertEquals('Start Event', $nodes[0]->getName());
        self::assertEquals(TypeEnum::CUSTOM, $nodes[0]->getType());
        self::assertEquals(HandlerEnum::EVENT, $nodes[0]->getHandler());

        self::assertEquals('Connector DEF', $nodes[1]->getName());
        self::assertEquals(TypeEnum::CONNECTOR, $nodes[1]->getType());
        self::assertEquals(HandlerEnum::ACTION, $nodes[1]->getHandler());

        self::assertEquals('Mapper XYZ', $nodes[2]->getName());
        self::assertEquals(TypeEnum::MAPPER, $nodes[2]->getType());
        self::assertEquals(HandlerEnum::ACTION, $nodes[2]->getHandler());

        self::assertEquals('Parser ABC', $nodes[3]->getName());
        self::assertEquals(TypeEnum::XML_PARSER, $nodes[3]->getType());
        self::assertEquals(HandlerEnum::ACTION, $nodes[3]->getHandler());
        self::assertEquals(1, count($nodes[3]->getNext()));
        self::assertEquals('Connector DEF', $nodes[3]->getNext()[0]->getName());

        self::assertEquals('Splitter SPI', $nodes[4]->getName());
        self::assertEquals(TypeEnum::SPLITTER, $nodes[4]->getType());
        self::assertEquals(HandlerEnum::ACTION, $nodes[4]->getHandler());

        self::assertEquals('Event 1', $nodes[5]->getName());
        self::assertEquals(TypeEnum::CRON, $nodes[5]->getType());
        self::assertEquals(HandlerEnum::EVENT, $nodes[5]->getHandler());
        self::assertEquals(1, count($nodes[5]->getNext()));
        self::assertEquals('*/2 * * * *', $nodes[5]->getCron());
        self::assertEquals('Parser ABC', $nodes[5]->getNext()[0]->getName());

        self::assertEquals('Event 2', $nodes[6]->getName());
        self::assertEquals(TypeEnum::WEBHOOK, $nodes[6]->getType());
        self::assertEquals(HandlerEnum::EVENT, $nodes[6]->getHandler());
    }

}