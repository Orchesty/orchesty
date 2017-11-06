<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Plugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Model\Plugins\PluginsManager;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use LogicException;
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
     * @covers PluginsManager::install()
     * @covers PluginsManager::getUrl()
     */
    public function testInstall(): void
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setToken('tkn')
            ->setSystem('sys');

        /** @var SystemManager|PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->createMock(SystemManager::class);
        $manager->expects($this->once())
            ->method('installSystem')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);

        /** @var StartingPoint|PHPUnit_Framework_MockObject_MockObject $start */
        $start = $this->createMock(StartingPoint::class);

        $plug = new PluginsManager($dm, $start, $manager);
        $req  = new Request();
        $req->headers->set(PluginHeadersEnum::TOKEN, 'tkn');
        $req->headers->set(PluginHeadersEnum::GUID, 'usr');
        $req->headers->set(PluginHeadersEnum::SYSTEM, 'sys');
        $req->headers->set(PluginHeadersEnum::VERSION, 'ver');

        $res = $plug->install($req);
        self::assertEquals([
            'key'            => 'sys',
            'token'          => 'tkn',
            'synchronized'   => FALSE,
            'plugin_version' => 'ver',
            'system_url'     => 'https://:/',
        ], $res);
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
                SystemInstall::SYSTEM_URL => 'https://:/',
            ]);

        $res = $plug->check($sys, new Request([], [], [], [], [], [], json_encode([
            SystemInstall::PLUGIN_VERSION => 'ver',
        ])));

        self::assertEquals([
            'key'            => 'sys',
            'token'          => 'tkn',
            'synchronized'   => FALSE,
            'plugin_version' => 'ver',
            'system_url'     => 'https://:/',
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
                SystemInstall::SYSTEM_URL => 'https://yoru/',
            ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('System url from request [https://:/] does not matched saved url in systemInstall [https://yoru/].');

        $plug->check($sys, new Request([], [], [], [], [], [], json_encode([
            SystemInstall::PLUGIN_VERSION => 'ver',
        ])));
    }

    /**
     * @covers PluginsManager::createSubscriber()
     * @covers PluginsManager::startTopologies()
     */
    public function testCreateSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('sys');

        $sp = $this->mockStartingPoint(
            'sys-' . TopologyNameUtils::CREATED_SUBSCRIBERS,
            'sys-start-node',
            '{"data":"data"}'
        );

        $dm = $this->mockDm(TopologyNameUtils::CREATED_SUBSCRIBERS);

        $plug = $this->mockPluginsManager($sp, $dm);
        $plug->createSubscriber($sys, ['data' => 'data']);
    }

    /**
     * @covers PluginsManager::createSubscriber()
     * @covers PluginsManager::startTopologies()
     */
    public function testUpdateSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('sys');

        $sp = $this->mockStartingPoint(
            'sys-' . TopologyNameUtils::UPDATED_SUBSCRIBERS,
            'sys-start-node',
            '{"data":"data"}'
        );

        $dm = $this->mockDm(TopologyNameUtils::UPDATED_SUBSCRIBERS);

        $plug = $this->mockPluginsManager($sp, $dm);
        $plug->createSubscriber($sys, ['data' => 'data']);
    }

    /**
     * @covers PluginsManager::createSubscriber()
     * @covers PluginsManager::startTopologies()
     */
    public function testDeleteSubscriber(): void
    {
        $sys = new SystemInstall();
        $sys->setSystem('sys');

        $sp = $this->mockStartingPoint(
            'sys-' . TopologyNameUtils::DELETED_SUBSCRIBERS,
            'sys-start-node',
            '{"data":"data"}'
        );

        $dm = $this->mockDm(TopologyNameUtils::DELETED_SUBSCRIBERS);

        $plug = $this->mockPluginsManager($sp, $dm);
        $plug->createSubscriber($sys, ['data' => 'data']);
    }

    /**
     * -------------------------------------- HELPERS --------------------------------------
     */

    /**
     * @param string $topology
     * @param string $node
     * @param string $data
     *
     * @return StartingPoint|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockStartingPoint(string $topology, string $node, string $data): StartingPoint
    {
        $sp = $this->createMock(StartingPoint::class);
        $sp->expects($this->once())
            ->method('run')->will($this->returnCallback(
                function (Topology $fTopology, Node $fNode, string $fData)
                use ($topology, $node, $data): void {
                    self::assertEquals($topology, $fTopology->getName());
                    self::assertEquals($node, $fNode->getName());
                    self::assertEquals($data, $fData);
                }
            ));

        return $sp;
    }

    /**
     * @param string $type
     *
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDm(string $type): DocumentManager
    {
        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->expects($this->once())
            ->method('getRunnableTopologies')->willReturn([(new Topology())->setName('sys-' . $type)]);

        $nodeRepo = $this->createMock(NodeRepository::class);
        $nodeRepo->expects($this->once())
            ->method('getStartingNode')->willReturn((new Node)->setName('sys-start-node'));

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))
            ->method('getRepository')->willReturn($topRepo);
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($nodeRepo);

        return $dm;
    }

    /**
     * @param StartingPoint|NULL   $start
     * @param DocumentManager|NULL $dm
     *
     * @return PluginsManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockPluginsManager(?StartingPoint $start = NULL, ?DocumentManager $dm = NULL): PluginsManager
    {
        /** @var SystemManager|PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->createMock(SystemManager::class);

        if (!$dm) {
            /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
            $dm = $this->createMock(DocumentManager::class);
        }

        if (!$start) {
            /** @var StartingPoint|PHPUnit_Framework_MockObject_MockObject $start */
            $start = $this->createMock(StartingPoint::class);
        }

        return new PluginsManager($dm, $start, $manager);
    }

}