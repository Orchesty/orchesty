<?php declare(strict_types=1);

namespace Tests\Integration\Configurator\Model;

use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Nette\Utils\Json;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologyManagerTest
 *
 * @package Tests\Integration\Configurator\Model
 */
final class TopologyManagerTest extends DatabaseTestCaseAbstract
{

    /**
     *
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
     *
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
     *
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
     *
     */
    public function testNameAndVersionTopology(): void
    {
        $manager = $this->container->get('hbpf.configurator.manager.topology');

        $topology = $manager->createTopology(['name' => 'Topology']);
        self::assertEquals(1, $topology->getVersion());

        $topology = $manager->updateTopology($topology, ['name' => 'Topology']);
        self::assertEquals(2, $topology->getVersion());

        $topology->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->flush();

        self::expectException(TopologyException::class);
        self::expectExceptionCode(TopologyException::TOPOLOGY_CANNOT_CHANGE_NAME);
        $manager->updateTopology($topology, ['name' => 'Another Topology']);
    }

    /**
     *
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
     *
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
     *
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
     *
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

        $node = new Node();
        $node
            ->setName('node name')
            ->setType(TypeEnum::CONNECTOR)
            ->setTopology($top->getId())
            ->setHandler(HandlerEnum::EVENT)
            ->setEnabled(TRUE);

        $this->dm->persist($node);
        $this->dm->flush($node);
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

        $nodes     = $this->dm->getRepository(Node::class)->findBy(['topology' => $res->getId()]);
        $nodeClone = $nodes[0];

        self::assertEquals($node->getName(), $nodeClone->getName());
        self::assertEquals($node->getType(), $nodeClone->getType());
        self::assertEquals($res->getId(), $nodeClone->getTopology());
        self::assertEquals($node->getHandler(), $nodeClone->getHandler());
        self::assertEquals($node->isEnabled(), $nodeClone->isEnabled());
    }

    /**
     *
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
     *
     */
    public function testSaveTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->persistAndFlush($topology);

        $topologyManager = $this->container->get('hbpf.configurator.manager.topology');
        $topologyManager->saveTopologySchema($topology, '', $this->getSchema('schema-1.json'));

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);

        self::assertEquals(7, count($nodes));
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
        self::assertEquals('Parser ABC', $nodes[5]->getNext()[0]->getName());

        self::assertEquals('Event 2', $nodes[6]->getName());
        self::assertEquals(TypeEnum::WEBHOOK, $nodes[6]->getType());
        self::assertEquals(HandlerEnum::EVENT, $nodes[6]->getHandler());
    }

    /**
     *
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

        $schema = $this->getSchema('schema-1.json');
        unset($schema['bpmn:process']['bpmn:startEvent']['@name']);
        $topologyManager->saveTopologySchema($topology, '', $schema);
    }

    /**
     *
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

        $schema = $this->getSchema('schema-1.json');
        unset($schema['bpmn:process']['bpmn:startEvent']['@pipes:pipesType']);
        $topologyManager->saveTopologySchema($topology, '', $schema);
    }

    /**
     *
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

        $schema                                                        = $this->getSchema('schema-1.json');
        $schema['bpmn:process']['bpmn:startEvent']['@pipes:pipesType'] = 'Unknown';
        $topologyManager->saveTopologySchema($topology, '', $schema);
    }

    /**
     *
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
    private function getSchema(string $name = 'schema-1.json'): array
    {
        return Json::decode(file_get_contents(sprintf('%s/data/%s', __DIR__, $name)), Json::FORCE_ARRAY);
    }

}