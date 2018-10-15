<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 13.11.17
 * Time: 8:37
 */

namespace Tests\Unit\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Installer\CategoryParser;
use CleverConnectors\AppBundle\Model\Installer\Dto\CompareResultDto;
use CleverConnectors\AppBundle\Model\Installer\Dto\TopologyFile;
use CleverConnectors\AppBundle\Model\Installer\Dto\UpdateObject;
use CleverConnectors\AppBundle\Model\Installer\InstallManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\RequestHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use ReflectionException;
use Tests\PrivateTrait;

/**
 * Class InstallManager
 *
 * @package Tests\Unit\AppBundle\Model\Installer
 */
final class InstallManagerTest extends TestCase
{

    use PrivateTrait;

    /**
     * @throws CleverConnectorsException
     */
    public function testPrepareInstall(): void
    {
        $topo = new Topology();
        $data = $this->createRedisRecord();

        $manager = $this->createManager($data, $topo, []);
        $output  = $manager->prepareInstall(TRUE, TRUE, TRUE);
        self::assertTrue(is_array($output));
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
    }

    /**
     * @throws CleverConnectorsException
     */
    public function testMakeInstall(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');
        $data = $this->createRedisRecord();

        $manager = $this->createManager($data, $topo, []);
        $output  = $manager->makeInstall(TRUE, TRUE, TRUE);
        self::assertTrue(is_array($output));
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
        self::assertNotEmpty($output['delete']);
    }

    /**
     * @throws CleverConnectorsException
     * @throws ReflectionException
     */
    public function testMakeInstallEx(): void
    {
        $topo = new Topology();
        $this->setProperty($topo, 'id', '123');

        $manager = $this->createManager(NULL, $topo, []);
        $this->expectException(CleverConnectorsException::class);
        $manager->makeInstall(TRUE, TRUE, TRUE);
    }

    /**
     * @param null|string $redisResult
     * @param Topology    $savedTopo
     * @param array       $dirs
     *
     * @return InstallManager
     * @throws ReflectionException
     */
    private function createManager(
        ?string $redisResult,
        Topology $savedTopo,
        array $dirs = []
    ): InstallManager
    {
        /** @var DocumentManager|MockObject $dm */
        $repo = $this->createMock(TopologyRepository::class);
        $dm   = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);
        $dm->method('persist')->willReturn(TRUE);

        /** @var Client|MockObject $client */
        $client = $this->getMockBuilder(Client::class)->setMethods(['set', 'get', 'del'])->getMock();
        $client->method('set')->willReturn(TRUE);
        $client->method('get')->willReturn($redisResult);
        $client->method('del')->willReturn(TRUE);

        /** @var TopologyManager|MockObject $topologyManager */
        $topologyManager = $this->createMock(TopologyManager::class);
        $topologyManager->method('createTopology')->willReturn(new Topology());
        $topologyManager->method('publishTopology')->willReturn(TRUE);
        $topologyManager->method('updateTopology')->willReturn($savedTopo);
        $topologyManager->method('deleteTopology')->willReturn(TRUE);
        $topologyManager->method('saveTopologySchema')->willReturn($savedTopo);

        /** @var RequestHandler|MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);
        $requestHandler->method('runTopology')->willReturn(new ResponseDto(200, '', '', []));
        $requestHandler->method('deleteTopology')->willReturn(new ResponseDto(200, '', '', []));

        /** @var CategoryParser|MockObject $categoryParser */
        $categoryParser = $this->createMock(CategoryParser::class);
        $categoryParser->method('classifyTopology')->willReturn(NULL);

        return new InstallManager($dm, $client, $topologyManager, $requestHandler, $categoryParser, $dirs);
    }

    /**
     * @return string
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