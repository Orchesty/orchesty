<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 13.11.17
 * Time: 8:37
 */

namespace Tests\Unit\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Installer\Dto\CompareResultDto;
use CleverConnectors\AppBundle\Model\Installer\Dto\TopologyFile;
use CleverConnectors\AppBundle\Model\Installer\Dto\UpdateObject;
use CleverConnectors\AppBundle\Model\Installer\InstallManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\TopologyGenerator\Request\RequestHandler;
use PHPUnit\Framework\TestCase;
use Predis\Client;
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
     *
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
     *
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
        self::assertEmpty($output['delete']);
    }

    /**
     *
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
     * @param string        $redisResult
     * @param null|Topology $savedTopo
     * @param array         $dirs
     *
     * @return InstallManager
     */
    private function createManager(
        ?string $redisResult,
        Topology $savedTopo,
        array $dirs = []
    ): InstallManager
    {
        $repo = $this->createMock(TopologyRepository::class);
        $dm   = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);
        $dm->method('persist')->willReturn(TRUE);

        $client = $this->getMockBuilder(Client::class)->setMethods(['set', 'get', 'del'])->getMock();
        $client->method('set')->willReturn(TRUE);
        $client->method('get')->willReturn($redisResult);
        $client->method('del')->willReturn(TRUE);

        $topologyManager = $this->createMock(TopologyManager::class);
        $topologyManager->method('createTopology')->willReturn(new Topology());
        $topologyManager->method('publishTopology')->willReturn(TRUE);
        $topologyManager->method('updateTopology')->willReturn($savedTopo);
        $topologyManager->method('deleteTopology')->willReturn(TRUE);
        $topologyManager->method('saveTopologySchema')->willReturn($savedTopo);

        $requestHandler = $this->createMock(RequestHandler::class);
        $requestHandler->method('runTopology')->willReturn(new ResponseDto(200, '', '', []));
        $requestHandler->method('deleteTopology')->willReturn(new ResponseDto(200, '', '', []));

        return new InstallManager($dm, $client, $topologyManager, $requestHandler, $dirs);
    }

    /**
     * @return string
     */
    private function createRedisRecord(): string
    {
        $topo = new Topology;
        $topo->setName('file-upl');

        $topo2 = new Topology;
        $topo2->setName('file-del');
        $this->setProperty($topo2, 'id', '345');

        $dto = new CompareResultDto();
        $dto->addCreate(new TopologyFile('file.tplg', __DIR__ . '/data/file.tplg'));
        $dto->addUpdate(new UpdateObject($topo, new TopologyFile('file-upl.tplg', __DIR__ . '/data/file-upl.tplg')));
        $dto->addDelete([$topo2]);

        return serialize($dto);
    }

}