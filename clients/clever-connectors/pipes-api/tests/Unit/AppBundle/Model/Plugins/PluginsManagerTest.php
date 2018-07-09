<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Plugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Model\CM\ListConnector\CMCreateDistributionListConnector;
use CleverConnectors\AppBundle\Model\CM\ListConnector\CMGetDistributionsConnector;
use CleverConnectors\AppBundle\Model\Plugins\PluginsManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Model\Systems\SystemTopologyRunner;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Tests\KernelTestCaseAbstract;

/**
 * Class PluginsManagerTest
 *
 * @package Tests\Unit\AppBundle\Model\Plugins
 */
final class PluginsManagerTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testInstall(): void
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setToken('tkn')
            ->setSystem('sys')
            ->setSettings(['system_url' => 'https://abc']);

        /** @var SystemManager|MockObject $manager */
        $manager = $this->createMock(SystemManager::class);
        $manager
            ->expects($this->once())
            ->method('installSystem')
            ->willReturn($sys);
        $manager
            ->expects($this->exactly(1))
            ->method('getSystemInstallOrNull')
            ->willReturn(NULL);

        $plug = $this->mockPluginsManager(NULL, NULL, $manager);
        $req  = new Request();
        $req->headers->set(PluginHeadersEnum::TOKEN, 'tkn');
        $req->headers->set(PluginHeadersEnum::GUID, 'usr');
        $req->headers->set(PluginHeadersEnum::SYSTEM, 'sys');
        $req->headers->set(PluginHeadersEnum::VERSION, 'ver');
        $req->request->set('remote_host', 'abc');

        $res = $plug->install($req);
        self::assertEquals([
            'system'             => 'sys',
            'token'              => 'tkn',
            'synchronized'       => FALSE,
            'pluginVersion'      => 'ver',
            'system_url'         => 'https://abc',
            'eventCreate'        => FALSE,
            'eventUnsubscribe'   => FALSE,
            'eventHardBounce'    => FALSE,
            'eventSubscribe'     => FALSE,
            'distribution_lists' => [],
        ], $res);
    }

    /**
     * @covers PluginsManager::install()
     * @covers PluginsManager::getUrl()
     *
     * @throws Exception
     */
    public function testInstall2(): void
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setToken('tkn')
            ->setSystem('sys')
            ->setSettings(['system_url' => 'https://abc']);

        /** @var SystemManager|MockObject $manager */
        $manager = $this->createMock(SystemManager::class);
        $manager
            ->expects($this->exactly(0))
            ->method('installSystem')
            ->willReturn($sys);
        $manager
            ->expects($this->once())
            ->method('getSystemInstallOrNull')
            ->willReturn($sys);

        $plug = $this->mockPluginsManager(NULL, NULL, $manager);
        $req  = new Request();
        $req->headers->set(PluginHeadersEnum::TOKEN, 'tkn');
        $req->headers->set(PluginHeadersEnum::GUID, 'usr');
        $req->headers->set(PluginHeadersEnum::SYSTEM, 'sys');
        $req->headers->set(PluginHeadersEnum::VERSION, 'ver');
        $req->request->set('remote_host', 'http://abc');

        $res = $plug->install($req);
        self::assertEquals([
            'system'             => 'sys',
            'token'              => 'tkn',
            'synchronized'       => FALSE,
            'pluginVersion'      => 'ver',
            'system_url'         => 'https://abc',
            'eventCreate'        => FALSE,
            'eventUnsubscribe'   => FALSE,
            'eventHardBounce'    => FALSE,
            'eventSubscribe'     => FALSE,
            'distribution_lists' => [],
        ], $res);
    }

    /**
     * @throws Exception
     */
    public function testInstall3(): void
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setToken('tkn_abc')
            ->setSystem('sys')
            ->setSettings(['system_url' => 'https://abc']);

        /** @var SystemManager|MockObject $manager */
        $manager = $this->createMock(SystemManager::class);
        $manager
            ->expects($this->exactly(0))
            ->method('installSystem')
            ->willReturn($sys);
        $manager
            ->expects($this->exactly(1))
            ->method('getSystemInstallOrNull')
            ->willReturn($sys);

        $plug = $this->mockPluginsManager(NULL, NULL, $manager);
        $req  = new Request();
        $req->headers->set(PluginHeadersEnum::TOKEN, 'tkn');
        $req->headers->set(PluginHeadersEnum::GUID, 'usr');
        $req->headers->set(PluginHeadersEnum::SYSTEM, 'sys');
        $req->headers->set(PluginHeadersEnum::VERSION, 'ver');
        $req->request->set('remote_host', 'http://abc');

        $res = $plug->install($req);
        self::assertEquals('tkn', $res['token']);
    }

    /**
     * @throws Exception
     */
    public function testInstall4(): void
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setToken('tkn')
            ->setSystem('sys')
            ->setSettings(['system_url' => 'https://abc_xyz']);

        /** @var SystemManager|MockObject $manager */
        $manager = $this->createMock(SystemManager::class);
        $manager
            ->expects($this->exactly(0))
            ->method('installSystem')
            ->willReturn($sys);
        $manager
            ->expects($this->exactly(1))
            ->method('getSystemInstallOrNull')
            ->willReturn($sys);

        $plug = $this->mockPluginsManager(NULL, NULL, $manager);
        $req  = new Request();
        $req->headers->set(PluginHeadersEnum::TOKEN, 'tkn');
        $req->headers->set(PluginHeadersEnum::GUID, 'usr');
        $req->headers->set(PluginHeadersEnum::SYSTEM, 'sys');
        $req->headers->set(PluginHeadersEnum::VERSION, 'ver');
        $req->request->set('remote_host', 'http://abc');

        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::MISMATCH_URL);

        $plug->install($req);
    }

    /**
     * @throws Exception
     */
    public function testUninstall(): void
    {
        /** @var SystemManager|MockObject $manager */
        $manager = $this->createMock(SystemManager::class);
        $manager
            ->expects($this->once())
            ->method('uninstallSystem')
            ->willReturn(TRUE);

        $plug = $this->mockPluginsManager(NULL, NULL, $manager);
        $req  = new Request();
        $req->headers->set(PluginHeadersEnum::TOKEN, 'tkn');
        $req->headers->set(PluginHeadersEnum::GUID, 'usr');
        $req->headers->set(PluginHeadersEnum::SYSTEM, 'sys');
        $req->headers->set(PluginHeadersEnum::VERSION, 'ver');
        $req->request->set('remote_host', 'abc');

        $res = $plug->uninstall($req);
        self::assertEquals([], $res);
    }

    /**
     * @covers PluginsManager::check()
     * @covers PluginsManager::systemToArray()
     *
     * @throws Exception
     */
    public function testCheck(): void
    {
        $plug = $this->mockPluginsManager();
        $sys  = new SystemInstall();
        $sys->setPluginVersion('ver')
            ->setSystem('sys')
            ->setUser('usr')
            ->setToken('tkn')
            ->setSettings([
                SystemInstall::SYSTEM_URL => 'https://abc',
            ]);

        $req = new Request([], [], [], [], [], [], json_encode([SystemInstall::PLUGIN_VERSION => 'ver']));
        $req->request->set('remote_host', 'http://abc');
        $res = $plug->check($sys, $req);

        self::assertEquals([
            'system'             => 'sys',
            'token'              => 'tkn',
            'synchronized'       => FALSE,
            'pluginVersion'      => 'ver',
            'system_url'         => 'https://abc',
            'eventCreate'        => FALSE,
            'eventUnsubscribe'   => FALSE,
            'eventHardBounce'    => FALSE,
            'eventSubscribe'     => FALSE,
            'distribution_lists' => [],
        ], $res);
    }

    /**
     * @covers PluginsManager::check()
     *
     * @throws Exception
     */
    public function testWrongUrl(): void
    {
        $plug = $this->mockPluginsManager();
        $sys  = new SystemInstall();
        $sys->setPluginVersion('ver')
            ->setSettings([
                SystemInstall::SYSTEM_URL => 'https://yoru',
            ]);

        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('System url from request [https://abc] does not matched saved url in systemInstall [https://yoru].');

        $req = new Request([], [], [], [], [], [], json_encode([SystemInstall::PLUGIN_VERSION => 'ver']));
        $req->request->set('remote_host', 'http://abc');
        $plug->check($sys, $req);
    }

    /**
     * @covers PluginsManager::createSubscriber()
     * @covers PluginsManager::startTopologies()
     *
     * @throws Exception
     */
    public function testCreateSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('null.user.group')->setUser('usr');

        $plug = $this->mockPluginsManager();
        $plug->createSubscriber($sys, new Request());
    }

    /**
     * @covers PluginsManager::updateSubscriber()
     * @covers PluginsManager::startTopologies()
     *
     * @throws Exception
     */
    public function testUpdateSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('null.user.group')->setUser('usr');

        $plug = $this->mockPluginsManager();
        $plug->createSubscriber($sys, new Request());
    }

    /**
     * @covers PluginsManager::deleteSubscriber()
     * @covers PluginsManager::startTopologies()
     * @throws Exception
     */
    public function testDeleteSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('null.user.group')->setUser('usr');

        $plug = $this->mockPluginsManager();
        $plug->createSubscriber($sys, new Request());
    }

    /**
     * @covers PluginsManager::validateSubscriber()
     * @covers PluginsManager::startTopologies()
     *
     * @throws Exception
     */
    public function testValidateSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('null.user.group')->setUser('usr');

        $plug = $this->mockPluginsManager();
        $plug->createSubscriber($sys, new Request());
    }

    /**
     * -------------------------------------- HELPERS --------------------------------------
     */

    /**
     * @param SystemTopologyRunner|null $systemTopologyRunner
     * @param DocumentManager|null      $dm
     * @param SystemManager|null        $manager
     *
     * @return PluginsManager
     * @throws Exception
     */
    private function mockPluginsManager(
        ?SystemTopologyRunner $systemTopologyRunner = NULL,
        ?DocumentManager $dm = NULL,
        ?SystemManager $manager = NULL
    ): PluginsManager
    {
        if (!$manager) {
            /** @var SystemManager|MockObject $manager */
            $manager = $this->createMock(SystemManager::class);
        }

        if (!$dm) {
            /** @var DocumentManager|MockObject $dm */
            $dm = $this->createMock(DocumentManager::class);
        }

        if (!$systemTopologyRunner) {
            /** @var SystemTopologyRunner|MockObject $start */
            $systemTopologyRunner = $this->createMock(SystemTopologyRunner::class);
        }

        /** @var CMGetDistributionsConnector|MockObject $distConn */
        $distConn = $this->createMock(CMGetDistributionsConnector::class);
        $distConn->method('getDistributionsArray')->willReturn([]);

        /** @var CMCreateDistributionListConnector|MockObject $createdListConn */
        $createdListConn = $this->createMock(CMCreateDistributionListConnector::class);
        $createdListConn->method('createList')->willReturn([]);

        $loader = $this->ownContainer->get('cc.systems.loader');

        return new PluginsManager($dm, $manager, $loader, $distConn, $systemTopologyRunner, $createdListConn);
    }

}