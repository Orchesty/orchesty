<?php declare(strict_types=1);

namespace Tests\Integration\ApiGateway\Manager;

use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
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
            ->setStatus(TopologyStatusEnum::DRAFT)
            ->setDescr('asd')
            ->setName('asdd')
            ->setBpmn('bpmn')
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
        self::assertEquals('bpmn', $top->getBpmn());
        self::assertFalse($top->isEnabled());
    }

    /**
     *
     */
    public function testSaveTopologySchema(): void
    {
        $top = new Topology();
        $top
            ->setName('asd')
            ->setDescr('wer')
            ->setStatus(TopologyStatusEnum::PUBLIC)
            ->setEnabled(FALSE)
            ->setBpmn('asdd');
        $this->dm->persist($top);

        $data = [
            'name'    => NULL,
            'descr'   => 'qwe',
            'enabled' => TRUE,
        ];

        /** @var Topology $res */
        $res = $this->container->get('hbpf.manager.topology')->saveTopologySchema($top, $data);
        self::assertNotEquals($top->getId(), $res->getId());
        self::assertEquals($top->getName() . ' - copy', $res->getName());
        self::assertEquals($data['descr'], $res->getDescr());
        self::assertEquals($data['enabled'], $res->isEnabled());
    }

    /**
     *
     */
    public function testPublishTopology(): void
    {
        $top = new Topology();
        $top->setName('asd')->setStatus(TopologyStatusEnum::DRAFT);
        /** @var Topology $res */
        $res = $this->container->get('hbpf.manager.topology')->publishTopology($top);
        self::assertEquals(TopologyStatusEnum::PUBLIC, $res->getStatus());
    }

    /**
     *
     */
    public function testCloneTopology(): void
    {
        $top = new Topology();
        $top
            ->setName('name')
            ->setStatus(TopologyStatusEnum::PUBLIC)
            ->setEnabled(FALSE)
            ->setDescr('desc')
            ->setBpmn('asd');

        /** @var Topology $res */
        $res = $this->container->get('hbpf.manager.topology')->cloneTopology($top);

        self::assertEquals($top->getName() . ' - copy', $res->getName());
        self::assertEquals($top->getDescr(), $res->getDescr());
        self::assertEquals(TopologyStatusEnum::DRAFT, $res->getStatus());
        self::assertEquals($top->isEnabled(), $res->isEnabled());
    }

}