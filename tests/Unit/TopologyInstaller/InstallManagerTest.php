<?php declare(strict_types=1);

namespace Tests\Unit\TopologyInstaller;

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
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Client;
use Tests\KernelTestCaseAbstract;

/**
 * Class InstallManagerTest
 *
 * @package Tests\Unit\TopologyInstaller
 */
final class InstallManagerTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testPrepareInstall(): void
    {
        $topo = new Topology();
        $data = $this->createRedisRecord();

        $manager = $this->createManager($data, $topo, []);
        $output  = $manager->prepareInstall(TRUE, TRUE, TRUE);
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
    }

    /**
     * @throws Exception
     */
    public function testMakeInstall(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');
        $data = $this->createRedisRecord();

        $manager = $this->createManager($data, $topo, []);
        $output  = $manager->makeInstall(TRUE, TRUE, TRUE);
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
        self::assertNotEmpty($output['delete']);
    }

    /**
     * @throws Exception
     */
    public function testMakeInstallEx(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');

        $manager = $this->createManager(NULL, $topo, []);
        $this->expectException(ConnectorException::class);
        $manager->makeInstall(TRUE, TRUE, TRUE);
    }

    /**
     * @param string|null $redisResult
     * @param Topology    $savedTopo
     * @param mixed[]     $dirs
     *
     * @return InstallManager
     * @throws Exception
     */
    private function createManager(?string $redisResult, Topology $savedTopo, array $dirs = []): InstallManager
    {
        $repo = $this->createMock(TopologyRepository::class);
        /** @var DocumentManager|MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);
        $dm->method('persist')->willReturn(TRUE);

        /** @var Client<mixed>|MockObject $client */
        $client = $this->getMockBuilder(Client::class)->setMethods(['set', 'get', 'del'])->getMock();
        $client->method('set')->willReturn(TRUE);
        $client->method('get')->willReturn($redisResult);
        $client->method('del')->willReturn(TRUE);

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

        return new InstallManager($dm, $client, $topologyManager, $requestHandler, $categoryParser, $decoder, $dirs);
    }

    /**
     * @return string
     * @throws Exception
     */
    private function createRedisRecord(): string
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

        return serialize($dto);
    }

}
