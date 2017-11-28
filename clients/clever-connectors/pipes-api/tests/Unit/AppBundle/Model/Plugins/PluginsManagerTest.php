<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Plugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Model\CM\ListConnector\CMGetDistributionsConnector;
use CleverConnectors\AppBundle\Model\Plugins\PluginsManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use PHPUnit_Framework_MockObject_MockObject;
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
     *
     */
    public function testInstall(): void
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setToken('tkn')
            ->setSystem('sys')
            ->setSettings(['system_url' => 'https://abc']);

        /** @var SystemManager|PHPUnit_Framework_MockObject_MockObject $manager */
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
     */
    public function testInstall2(): void
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setToken('tkn')
            ->setSystem('sys')
            ->setSettings(['system_url' => 'https://abc']);

        /** @var SystemManager|PHPUnit_Framework_MockObject_MockObject $manager */
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
            'pluginVersion'      => NULL,
            'system_url'         => 'https://abc',
            'eventCreate'        => FALSE,
            'eventUnsubscribe'   => FALSE,
            'eventHardBounce'    => FALSE,
            'eventSubscribe'     => FALSE,
            'distribution_lists' => [],
        ], $res);
    }

    /**
     *
     */
    public function testInstall3(): void
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setToken('tkn_abc')
            ->setSystem('sys')
            ->setSettings(['system_url' => 'https://abc']);

        /** @var SystemManager|PHPUnit_Framework_MockObject_MockObject $manager */
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
     *
     */
    public function testInstall4(): void
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setToken('tkn')
            ->setSystem('sys')
            ->setSettings(['system_url' => 'https://abc_xyz']);

        /** @var SystemManager|PHPUnit_Framework_MockObject_MockObject $manager */
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
     * @covers PluginsManager::check()
     * @covers PluginsManager::systemToArray()
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
     */
    public function testCreateSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('null.user.group')->setUser('usr');

        $sp = $this->mockStartingPoint(
            'sys-' . TopologyNameUtils::CREATED_SUBSCRIBERS,
            'sys-start-node'
        );

        $dm = $this->mockDm(TopologyNameUtils::CREATED_SUBSCRIBERS);

        $plug = $this->mockPluginsManager($sp, $dm);
        $plug->createSubscriber($sys, new Request());
    }

    /**
     * @covers PluginsManager::updateSubscriber()
     * @covers PluginsManager::startTopologies()
     */
    public function testUpdateSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('null.user.group')->setUser('usr');

        $sp = $this->mockStartingPoint(
            'sys-' . TopologyNameUtils::UPDATED_SUBSCRIBERS,
            'sys-start-node'
        );

        $dm = $this->mockDm(TopologyNameUtils::UPDATED_SUBSCRIBERS);

        $plug = $this->mockPluginsManager($sp, $dm);
        $plug->createSubscriber($sys, new Request());
    }

    /**
     * @covers PluginsManager::deleteSubscriber()
     * @covers PluginsManager::startTopologies()
     */
    public function testDeleteSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('null.user.group')->setUser('usr');

        $sp = $this->mockStartingPoint(
            'sys-' . TopologyNameUtils::DELETED_SUBSCRIBERS,
            'sys-start-node'
        );

        $dm = $this->mockDm(TopologyNameUtils::DELETED_SUBSCRIBERS);

        $plug = $this->mockPluginsManager($sp, $dm);
        $plug->createSubscriber($sys, new Request());
    }

    /**
     * @covers PluginsManager::validateSubscriber()
     * @covers PluginsManager::startTopologies()
     */
    public function testValidateSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('null.user.group')->setUser('usr');

        $sp = $this->mockStartingPoint(
            'sys-' . TopologyNameUtils::VALIDATE_SUBSCRIBERS,
            'sys-start-node'
        );

        $dm = $this->mockDm(TopologyNameUtils::VALIDATE_SUBSCRIBERS);

        $plug = $this->mockPluginsManager($sp, $dm);
        $plug->createSubscriber($sys, new Request());
    }

    /**
     * -------------------------------------- HELPERS --------------------------------------
     */

    /**
     * @param string $topology
     * @param string $node
     *
     * @return StartingPoint|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockStartingPoint(string $topology, string $node): StartingPoint
    {
        $sp = $this->createMock(StartingPoint::class);
        $sp->expects($this->once())
            ->method('runWithRequest')->will($this->returnCallback(
                function (Request $fRequest, Topology $fTopology, Node $fNode)
                use ($topology, $node): void {
                    self::assertEquals($topology, $fTopology->getName());
                    self::assertEquals($node, $fNode->getName());
                }
            ));

        return $sp;
    }

    /**
     * @param string $type
     *
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDm(string $type = ''): DocumentManager
    {
        $nodeRepo = $this->createMock(NodeRepository::class);
        $nodeRepo->expects($this->once())
            ->method('getStartingNode')->willReturn((new Node)->setName('sys-start-node'));

        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->expects($this->once())
            ->method('getRunnableTopologies')->willReturn([(new Topology())->setName('sys-' . $type)]);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))
            ->method('getRepository')->willReturn($topRepo);
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($nodeRepo);

        return $dm;
    }

    /**
     * @param StartingPoint|null   $start
     * @param DocumentManager|null $dm
     * @param SystemManager|null   $manager
     *
     * @return PluginsManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockPluginsManager(
        ?StartingPoint $start = NULL,
        ?DocumentManager $dm = NULL,
        ?SystemManager $manager = NULL
    ): PluginsManager
    {
        if (!$manager) {
            /** @var SystemManager|PHPUnit_Framework_MockObject_MockObject $manager */
            $manager = $this->createMock(SystemManager::class);
        }

        if (!$dm) {
            /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
            $dm = $this->createMock(DocumentManager::class);
        }

        if (!$start) {
            /** @var StartingPoint|PHPUnit_Framework_MockObject_MockObject $start */
            $start = $this->createMock(StartingPoint::class);
        }

        /** @var CMGetDistributionsConnector|PHPUnit_Framework_MockObject_MockObject $distConn */
        $distConn = $this->createMock(CMGetDistributionsConnector::class);
        $distConn->method('getDistributionsArray')->willReturn([]);

        $loader = $this->container->get('cc.systems.loader');

        return new PluginsManager($dm, $start, $manager, $loader, $distConn);
    }

}