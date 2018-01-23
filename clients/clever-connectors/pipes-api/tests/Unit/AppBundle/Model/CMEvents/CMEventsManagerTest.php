<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventsManager;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMEventsManagerTest
 *
 * @package Tests\Unit\AppBundle\Model\CMEvents
 */
final class CMEventsManagerTest extends KernelTestCaseAbstract
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
            ->method('getSystemInstallByEvent')->willReturn([
                (new SystemInstall())->setUser('usgfhr')->setSystem('null.user')->setToken('tok'),
            ]);

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

        $loader = $this->container->get('cc.systems.loader');

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);

        $mana = new CMEventsManager($dm, $handler, $loader, $systemLimitManager);
        $mana->runEvent(new Request(), '', SystemInstall::EVENT_CREATE);
    }

    /**
     *
     */
    public function testRunEvent(): void
    {
        $sysRepo = $this->createMock(SystemInstallRepository::class);
        $sysRepo->expects($this->once())
            ->method('getSystemInstallByEvent')->willReturn([
                (new SystemInstall())->setUser('usr')->setSystem('null.user')->setToken('tok'),
            ]);

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
        $loader  = $this->container->get('cc.systems.loader');

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);

        $mana = new CMEventsManager($dm, $handler, $loader, $systemLimitManager);
        $mana->runEvent(new Request(), '', SystemInstall::EVENT_CREATE);
    }

    /**
     *
     */
    public function testRunEventFindByName(): void
    {
        $sysRepo = $this->createMock(SystemInstallRepository::class);
        $sysRepo->expects($this->once())
            ->method('getSystemInstallByEvent')->willReturn([
                (new SystemInstall())->setUser('usr')->setSystem('null.user')->setToken('tok'),
            ]);

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
        $loader  = $this->container->get('cc.systems.loader');

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);

        $mana = new CMEventsManager($dm, $handler, $loader, $systemLimitManager);
        $mana->runEvent(new Request(), '', SystemInstall::EVENT_CREATE);
    }

    /**
     *
     */
    public function testSaveEventMissingTopology(): void
    {
        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->method('getRunnableTopologies')->willReturn([]);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))
            ->method('getRepository')->willReturn($this->createMock(SystemInstallRepository::class));
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($topRepo);
        $dm->expects($this->at(2))
            ->method('getRepository')->willReturn($this->createMock(NodeRepository::class));

        /** @var StartingPointHandler $handler */
        $handler = $this->createMock(StartingPointHandler::class);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::TOPOLOGY_NOT_FOUND);

        $systemInstall = (new SystemInstall())->setUser('usr')->setSystem('null.user')->setToken('tok');
        $data          = [SystemInstall::EVENT_HARD_BOUNCE => TRUE];

        $loader = $this->container->get('cc.systems.loader');

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);

        $mana = new CMEventsManager($dm, $handler, $loader, $systemLimitManager);
        $mana->saveEventsForSystemInstall($systemInstall, $data);
    }

    /**
     *
     */
    public function testSaveEvent(): void
    {
        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->expects($this->once())
            ->method('getRunnableTopologies')->willReturn([(new Topology())->setName('top-name')]);

        $nodeRepo = $this->createMock(NodeRepository::class);
        $nodeRepo->expects($this->once())
            ->method('getStartingNode')->willReturn((new Node())->setName('node-name'));

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))
            ->method('getRepository')->willReturn($this->createMock(SystemInstallRepository::class));
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($topRepo);
        $dm->expects($this->at(2))
            ->method('getRepository')->willReturn($nodeRepo);

        /** @var StartingPointHandler $handler */
        $handler = $this->createMock(StartingPointHandler::class);

        $systemInstall = (new SystemInstall())->setUser('usr')->setSystem('null.user')->setToken('tok');
        $data          = [
            SystemInstall::EVENT_CREATE      => TRUE,
            SystemInstall::EVENT_HARD_BOUNCE => TRUE,
            SystemInstall::EVENT_UNSUBSCRIBE => TRUE,
            'settings'                       => [],
        ];

        $loader = $this->container->get('cc.systems.loader');

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);

        $mana = new CMEventsManager($dm, $handler, $loader, $systemLimitManager);
        $mana->saveEventsForSystemInstall($systemInstall, $data);
        self::assertArrayNotHasKey(SystemInstall::EVENT_CREATE, $data);
        self::assertArrayNotHasKey(SystemInstall::EVENT_HARD_BOUNCE, $data);
        self::assertArrayHasKey('settings', $data);
        self::assertTrue($systemInstall->isEventHardBounce());
        self::assertTrue($systemInstall->isEventCreate());
        self::assertTrue($systemInstall->isEventUnsubscribe());
    }

    /**
     *
     */
    public function testSaveEventFindByName(): void
    {
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
            ->method('getRepository')->willReturn($this->createMock(SystemInstallRepository::class));
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($topRepo);
        $dm->expects($this->at(2))
            ->method('getRepository')->willReturn($nodeRepo);

        /** @var StartingPointHandler $handler */
        $handler = $this->createMock(StartingPointHandler::class);

        $systemInstall = (new SystemInstall())->setUser('usr')->setSystem('null.user')->setToken('tok');
        $data          = [SystemInstall::EVENT_HARD_BOUNCE => TRUE, 'settings' => []];

        $loader = $this->container->get('cc.systems.loader');

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);

        $mana = new CMEventsManager($dm, $handler, $loader, $systemLimitManager);
        $mana->saveEventsForSystemInstall($systemInstall, $data);
        self::assertArrayNotHasKey(SystemInstall::EVENT_CREATE, $data);
        self::assertArrayHasKey('settings', $data);
    }

}