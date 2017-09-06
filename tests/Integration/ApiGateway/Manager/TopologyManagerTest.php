<?php declare(strict_types=1);

namespace Tests\Integration\ApiGateway\Manager;

use Hanaboso\PipesFramework\ApiGateway\Exception\TopologyException;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\Commons\Topology\TopologyRepository;
use Nette\Utils\Json;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologyManagerTest
 *
 * @package Tests\Integration\ApiGateway\Manager
 */
class TopologyManagerTest extends DatabaseTestCaseAbstract
{

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

        $this->container->get('hbpf.manager.topology')->updateTopology($top, $expt);
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
        /** @var Topology $res */
        $res = $this->container->get('hbpf.manager.topology')->publishTopology($top);
        self::assertEquals(TopologyStatusEnum::PUBLIC, $res->getVisibility());
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

        /** @var Topology $res */
        $res = $this->container->get('hbpf.manager.topology')->cloneTopology($top);

        self::assertEquals($top->getName() . ' - copy', $res->getName());
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

        $topologyManager = $this->container->get('hbpf.manager.topology');

        $topologyManager->saveTopologySchema(
            $topology,
            '',
            Json::decode(file_get_contents(sprintf('%s/data/schema-1.json', __DIR__)), Json::FORCE_ARRAY)
        );

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);

        self::assertEquals(3, count($nodes));
        self::assertEquals('startovaci-node', $nodes[0]->getName());
        self::assertEquals(HandlerEnum::EVENT, $nodes[0]->getHandler());
        self::assertEquals(1, count($nodes[0]->getNext()));
        self::assertEquals('task-node', $nodes[0]->getNext()[0]->getName());
        self::assertEquals('task-node', $nodes[1]->getName());
        self::assertEquals(HandlerEnum::ACTION, $nodes[1]->getHandler());
        self::assertEquals(1, count($nodes[1]->getNext()));
        self::assertEquals('end-node', $nodes[1]->getNext()[0]->getName());
        self::assertEquals('end-node', $nodes[2]->getName());
        self::assertEquals(HandlerEnum::EVENT, $nodes[2]->getHandler());
        self::assertEquals(0, count($nodes[2]->getNext()));

        $topologyManager->saveTopologySchema(
            $topology,
            '',
            Json::decode(file_get_contents(sprintf('%s/data/schema-2.json', __DIR__)), Json::FORCE_ARRAY)
        );

        $nodes = $this->dm->getRepository(Node::class)->findBy(['topology' => $topology->getId()]);

        self::assertEquals(3, count($nodes));
        self::assertEquals('startovaci-node', $nodes[0]->getName());
        self::assertEquals(HandlerEnum::EVENT, $nodes[0]->getHandler());
        self::assertEquals(2, count($nodes[0]->getNext()));
        self::assertEquals('task-node', $nodes[0]->getNext()[0]->getName());
        self::assertEquals('end-node', $nodes[0]->getNext()[1]->getName());
        self::assertEquals('task-node', $nodes[1]->getName());
        self::assertEquals(HandlerEnum::ACTION, $nodes[1]->getHandler());
        self::assertEquals(1, count($nodes[1]->getNext()));
        self::assertEquals('end-node', $nodes[2]->getName());
        self::assertEquals(HandlerEnum::EVENT, $nodes[2]->getHandler());
        self::assertEquals(0, count($nodes[2]->getNext()));
    }

    /**
     *
     */
    public function testDeleteTopology(): void
    {
        $manager = $this->container->get('hbpf.manager.topology');

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

}