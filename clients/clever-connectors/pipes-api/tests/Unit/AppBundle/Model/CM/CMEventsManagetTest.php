<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\CMEventsManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMEventsManagetTest
 *
 * @package Tests\Unit\AppBundle\Model\CM
 */
class CMEventsManagetTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testRunEventInvalidEvent(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::INVALID_ENUM_VALUE);

        /** @var CMEventsManager $mana */
        $mana = $this->container->get('cc.events.manager');
        $mana->runEvent(new Request(), '', '');
    }

    /**
     *
     */
    public function testRunEventMissingTopology(): void
    {
        $sysRepo = $this->createMock(SystemInstallRepository::class);
        $sysRepo->expects($this->once())
            ->method('getSystemInstallByEvent')->willReturn([(new SystemInstall())->setUser('usgfhr')->setSystem('ssghys')]);

        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->method('getRunnableTopologies')->willReturn([]);

        $nodeRepo = $this->createMock(NodeRepository::class);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))
            ->method('getRepository')->willReturn($sysRepo);
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($topRepo);
        $dm->expects($this->at(2))
            ->method('getRepository')->willReturn($nodeRepo);

        /** @var StartingPointHandler $handler */
        $handler = $this->createMock(StartingPointHandler::class);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::TOPOLOGY_NOT_FOUND);

        $mana = new CMEventsManager($dm, $handler);
        $mana->runEvent(new Request(), '', SystemInstall::EVENT_CREATE);
    }

    /**
     *
     */
    public function testRunEvent(): void
    {
        $sysRepo = $this->createMock(SystemInstallRepository::class);
        $sysRepo->expects($this->once())
            ->method('getSystemInstallByEvent')->willReturn([(new SystemInstall())->setUser('usr')->setSystem('ssys')]);

        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->expects($this->once())
            ->method('getRunnableTopologies')->willReturn([(new Topology())->setName('top-name')]);

        $nodeRepo = $this->createMock(NodeRepository::class);
        $nodeRepo->expects($this->once())
            ->method('getStartingNode')->willReturn((new Node())->setName('node-name'));

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))
            ->method('getRepository')->willReturn($sysRepo);
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($topRepo);
        $dm->expects($this->at(2))
            ->method('getRepository')->willReturn($nodeRepo);

        /** @var StartingPointHandler $handler */
        $handler = $this->createMock(StartingPointHandler::class);

        $mana = new CMEventsManager($dm, $handler);
        $mana->runEvent(new Request(), '', SystemInstall::EVENT_CREATE);
    }

    /**
     *
     */
    public function testRunEventFindByName(): void
    {
        $sysRepo = $this->createMock(SystemInstallRepository::class);
        $sysRepo->expects($this->once())
            ->method('getSystemInstallByEvent')->willReturn([(new SystemInstall())->setUser('usr')->setSystem('ssys')]);

        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->expects($this->at(0))
            ->method('getRunnableTopologies')->willReturn([]);
        $topRepo->expects($this->at(1))
            ->method('getRunnableTopologies')->willReturn([(new Topology())->setName('top-name')]);

        $nodeRepo = $this->createMock(NodeRepository::class);
        $nodeRepo->expects($this->once())
            ->method('getStartingNode')->willReturn((new Node())->setName('node-name'));

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))
            ->method('getRepository')->willReturn($sysRepo);
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($topRepo);
        $dm->expects($this->at(2))
            ->method('getRepository')->willReturn($nodeRepo);

        /** @var StartingPointHandler $handler */
        $handler = $this->createMock(StartingPointHandler::class);

        $mana = new CMEventsManager($dm, $handler);
        $mana->runEvent(new Request(), '', SystemInstall::EVENT_CREATE);
    }

}