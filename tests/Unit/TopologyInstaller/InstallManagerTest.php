<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\TopologyInstaller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\TopologyInstaller\CategoryParser;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject;
use Hanaboso\PipesFramework\TopologyInstaller\InstallManager;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PipesFrameworkTests\KernelTestCaseAbstract;
use Predis\Client;
use Predis\Connection\Parameters;
use ReflectionException;

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
     *
     * @throws Exception
     */
    public function testPrepareInstall(): void
    {
        $topo = new Topology();
        $this->createRedisRecord();

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
     *
     * @throws Exception
     */
    public function testMakeInstall(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');
        $this->createRedisRecord();

        $manager = $this->createManager($topo, []);
        $output  = $manager->makeInstall(TRUE, TRUE, TRUE);
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
        self::assertNotEmpty($output['delete']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeInstall
     *
     * @throws Exception
     */
    public function testMakeInstallEx(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');

        $manager = $this->createManager($topo, []);
        $this->expectException(ConnectorException::class);
        $manager->makeInstall(TRUE, TRUE, TRUE);
    }

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeDelete
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function testMakeDelete(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');
        $manager = $this->createManager($topo, []);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects(self::any())->method('persist')->willThrowException(new Exception());
        $this->setProperty($manager, 'dm', [$dm]);

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
        /** @var DocumentManager|MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);
        $dm->method('persist')->willReturn(TRUE);

        /** @var TopologyManager|MockObject $topologyManager */
        $topologyManager = $this->createMock(TopologyManager::class);
        $topologyManager->method('createTopology')->willReturn(new Topology());
        $topologyManager->method('publishTopology')->willReturn(new Topology());
        $topologyManager->method('updateTopology')->willReturn($savedTopo);
        $topologyManager->method('saveTopologySchema')->willReturn($savedTopo);
        $topologyManager->method('deleteTopology')->willReturnCallback(
            static function (): void {
            }
        );

        /** @var TopologyGeneratorBridge|MockObject $requestHandler */
        $requestHandler = $this->createMock(TopologyGeneratorBridge::class);
        $requestHandler->method('runTopology')->willReturn(new ResponseDto(200, '', '', []));
        $requestHandler->method('deleteTopology')->willReturn(new ResponseDto(200, '', '', []));

        /** @var CategoryParser|MockObject $categoryParser */
        $categoryParser = $this->createMock(CategoryParser::class);
        $categoryParser->method('classifyTopology')->willReturnCallback(
            static function (): void {
            }
        );
        $decoder = self::$container->get('rest.decoder.xml');

        return new InstallManager(
            $dm,
            $topologyManager,
            $requestHandler,
            $categoryParser,
            $decoder,
            (string) getenv('REDIS_DSN'),
            $dirs
        );
    }

    /**
     * @throws Exception
     */
    private function createRedisRecord(): void
    {
        $topo = new Topology();
        $topo->setName('file-upl');

        $topo2 = new Topology();
        $topo2->setName('file-del');
        $this->setProperty($topo2, 'id', '345');

        $dto = new CompareResultDto();
        $dto->addCreate(new TopologyFile('file.tplg', __DIR__ . '/data/file.tplg'));
        $dto->addUpdate(new UpdateObject($topo, new TopologyFile('file-upl.tplg', __DIR__ . '/data/file-upl.tplg')));
        $dto->addDelete([$topo2]);

        $client = new Client(Parameters::create((string) getenv('REDIS_DSN')));
        $client->set(InstallManager::AUTO_INSTALL_KEY, serialize($dto));
    }

}
