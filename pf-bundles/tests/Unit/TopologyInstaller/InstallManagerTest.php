<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\TopologyInstaller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\TopologyInstaller\Cache\NullCache;
use Hanaboso\PipesFramework\TopologyInstaller\CategoryParser;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject;
use Hanaboso\PipesFramework\TopologyInstaller\InstallManager;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository;
use Monolog\Logger;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class InstallManagerTest
 *
 * @package PipesFrameworkTests\Unit\TopologyInstaller
 */
final class InstallManagerTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::setLogger
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::prepareInstall
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::generateOutput
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\NullCache
     *
     * @throws Exception
     */
    public function testPrepareInstall(): void
    {
        $topo    = new Topology();
        $manager = $this->createManager($topo, []);
        $manager->setLogger(new Logger('logger'));
        $output = $manager->prepareInstall(TRUE, TRUE, TRUE);
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);

        $output = $manager->prepareInstall(TRUE, TRUE, TRUE, TRUE);
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
    }

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeInstall
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeCreate
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeUpdate
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeRunnable
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeDeletable
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::logException
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\NullCache
     *
     * @throws Exception
     */
    public function testMakeInstall(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');

        $manager = $this->createManager($topo, []);
        $output  = $manager->makeInstall(TRUE, TRUE, TRUE);
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
    }

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeInstall
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\NullCache
     *
     * @throws Exception
     */
    public function testMakeInstallEx(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');

        $manager = $this->createManager($topo, []);
        $output  = $manager->makeInstall(TRUE, TRUE, TRUE);
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
        self::assertEmpty($output['delete']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeCreate
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::logException
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\NullCache
     *
     * @throws Exception
     */
    public function testMakeCreateEx(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');
        $manager = $this->createManager($topo, []);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects(self::any())->method('persist')->willThrowException(new Exception());
        $this->setProperty($manager, 'dm', $dm);

        $dto = new CompareResultDto();
        $dto->addCreate(new TopologyFile('file.tplg', __DIR__ . '/data/file.tplg'));

        $result = $this->invokeMethod($manager, 'makeCreate', [$dto]);

        self::assertArrayHasKey('file', $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeUpdate
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::logException
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\NullCache
     *
     * @throws Exception
     */
    public function testMakeUpdateEx(): void
    {
        $topo = new Topology();
        $topo->setName('name');
        $this->setProperty($topo, 'id', '123');
        $manager = $this->createManager($topo, []);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects(self::any())->method('persist')->willThrowException(new Exception());
        $this->setProperty($manager, 'dm', $dm);

        $dto = new CompareResultDto();
        $dto->addUpdate(new UpdateObject($topo, new TopologyFile('file-upl.tplg', __DIR__ . '/data/file-upl.tplg')));

        $result = $this->invokeMethod($manager, 'makeUpdate', [$dto]);

        self::assertArrayHasKey('name', $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeDelete
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeDeletable
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\NullCache
     *
     * @throws Exception
     */
    public function testMakeDelete(): void
    {
        $topo = new Topology();
        $topo->setName('name');
        $this->setProperty($topo, 'id', '123');
        $manager = $this->createManager($topo, []);

        $dto = new CompareResultDto();
        $dto->addDelete([$topo]);

        $result = $this->invokeMethod($manager, 'makeDelete', [$dto]);

        self::assertEquals(['name' => ''], $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeDelete
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::logException
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\NullCache
     *
     * @throws Exception
     */
    public function testMakeDeleteEx(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');
        $manager = $this->createManager($topo, []);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects(self::any())->method('persist')->willThrowException(new Exception());
        $this->setProperty($manager, 'dm', $dm);

        $dto = new CompareResultDto();
        $dto->addDelete([(new Topology())->setName('name')]);

        $result = $this->invokeMethod($manager, 'makeDelete', [$dto]);

        self::assertArrayHasKey('name', $result);
    }

    /**
     * @param Topology $savedTopo
     * @param mixed[]  $dirs
     *
     * @return InstallManager
     * @throws Exception
     */
    private function createManager(Topology $savedTopo, array $dirs = []): InstallManager
    {
        $repo = $this->createMock(TopologyRepository::class);
        $dm   = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);
        $dm->method('persist')->willReturn(TRUE);

        $topologyManager = $this->createMock(TopologyManager::class);
        $topologyManager->method('createTopology')->willReturn(new Topology());
        $topologyManager->method('publishTopology')->willReturn(new Topology());
        $topologyManager->method('updateTopology')->willReturn($savedTopo);
        $topologyManager->method('saveTopologySchema')->willReturn($savedTopo);
        $topologyManager->method('deleteTopology')->willReturnCallback(
            static function (): void {
            }
        );

        $requestHandler = $this->createMock(TopologyGeneratorBridge::class);
        $requestHandler->method('runTopology')->willReturn(new ResponseDto(200, '', '', []));
        $requestHandler->method('deleteTopology')->willReturn(new ResponseDto(200, '', '', []));

        $categoryParser = $this->createMock(CategoryParser::class);
        $categoryParser->method('classifyTopology')->willReturnCallback(
            static function (): void {
            }
        );
        $decoder    = self::$container->get('rest.decoder.xml');
        $redisCache = new NullCache();

        return new InstallManager(
            $dm,
            $topologyManager,
            $requestHandler,
            $categoryParser,
            $decoder,
            $redisCache,
            $dirs,
            TRUE
        );
    }

}
