<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventsManager;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\Systems\SystemTopologyRunner;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
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
    public function testRunEvent(): void
    {
        $sysRepo = $this->createMock(SystemInstallRepository::class);
        $sysRepo->expects($this->once())
            ->method('getSystemInstallByEvent')->willReturn([
                (new SystemInstall())->setUser('usr')->setSystem('null.user')->setToken('tok'),
            ]);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($sysRepo);
        $loader = $this->container->get('cc.systems.loader');

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);

        /** @var SystemTopologyRunner $systemTopologyRunner */
        $systemTopologyRunner = $this->createMock(SystemTopologyRunner::class);

        $mana = new CMEventsManager($dm, $loader, $systemLimitManager, $systemTopologyRunner);
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

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($sysRepo);

        $loader = $this->container->get('cc.systems.loader');

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);

        /** @var SystemTopologyRunner $systemTopologyRunner */
        $systemTopologyRunner = $this->createMock(SystemTopologyRunner::class);

        $mana = new CMEventsManager($dm, $loader, $systemLimitManager, $systemTopologyRunner);
        $mana->runEvent(new Request(), '', SystemInstall::EVENT_CREATE);
    }

    /**
     *
     */
    public function testSaveEvent(): void
    {
        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($this->createMock(SystemInstallRepository::class));

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

        /** @var SystemTopologyRunner $systemTopologyRunner */
        $systemTopologyRunner = $this->createMock(SystemTopologyRunner::class);

        $mana = new CMEventsManager($dm, $loader, $systemLimitManager, $systemTopologyRunner);
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
        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($this->createMock(SystemInstallRepository::class));

        $systemInstall = (new SystemInstall())->setUser('usr')->setSystem('null.user')->setToken('tok');
        $data          = [SystemInstall::EVENT_HARD_BOUNCE => TRUE, 'settings' => []];

        $loader = $this->container->get('cc.systems.loader');

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);

        /** @var SystemTopologyRunner $systemTopologyRunner */
        $systemTopologyRunner = $this->createMock(SystemTopologyRunner::class);

        $mana = new CMEventsManager($dm, $loader, $systemLimitManager, $systemTopologyRunner);
        $mana->saveEventsForSystemInstall($systemInstall, $data);
        self::assertArrayNotHasKey(SystemInstall::EVENT_CREATE, $data);
        self::assertArrayHasKey('settings', $data);
    }

}