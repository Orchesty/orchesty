<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 13.11.17
 * Time: 13:14
 */

namespace Tests\Integration\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Installer\InstallManager;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\Request\RequestHandler;
use Predis\Client;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class InstallManagerTest
 *
 * @package Tests\Integration\AppBundle\Model\Installer
 */
final class InstallManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var Client
     */
    private $redis;

    /**
     *
     */
    public function testPrepareInstall(): void
    {
        $this->createTopologies();
        $manager = $this->getManager();
        $output  = $manager->prepareInstall(TRUE, TRUE, TRUE);
        self::assertTrue(is_array($output));
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
        self::assertEquals('inner-file', reset($output['create']));
        self::assertEquals('file', reset($output['update']));
        self::assertEquals('old-file', reset($output['delete']));

        $res = $this->redis->get(InstallManager::AUTO_INSTALL_KEY);
        self::assertNotEmpty($res);
        $this->redis->del([InstallManager::AUTO_INSTALL_KEY]);
    }

    /**
     *
     */
    public function testMakeInstall(): void
    {
        $this->dm->getConnection()->dropDatabase('pipes');
        $this->createTopologies();
        $manager = $this->getManager();
        $manager->prepareInstall(TRUE, TRUE, TRUE);
        $res = $this->redis->get(InstallManager::AUTO_INSTALL_KEY);
        self::assertNotEmpty($res);

        $output = $manager->makeInstall(TRUE, TRUE, TRUE);
        self::assertTrue(is_array($output));
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
        self::assertEquals(1, count($output['create']));
        self::assertEquals(1, count($output['update']));
        self::assertEquals(1, count($output['delete']));

        $res = $this->redis->get(InstallManager::AUTO_INSTALL_KEY);
        self::assertEmpty($res);
    }

    /**
     *
     */
    public function testMakeInstallEx(): void
    {
        $manager = $this->getManager();
        $this->expectException(CleverConnectorsException::class);
        $manager->makeInstall(TRUE, TRUE, TRUE);
    }

    /**
     * @return InstallManager
     */
    private function getManager(): InstallManager
    {
        $requestHandler = $this->createMock(RequestHandler::class);
        $requestHandler->method('runTopology')->willReturn(new ResponseDto(200, '', '', []));
        $requestHandler->method('deleteTopology')->willReturn(new ResponseDto(200, '', '', []));

        $this->redis     = $this->container->get('snc_redis.default');
        $topologyManager = $this->container->get('hbpf.configurator.manager.topology');
        $dir             = sprintf('%s/data', __DIR__);

        return new InstallManager($this->dm, $this->redis, $topologyManager, $requestHandler, [$dir]);
    }

    /**
     *
     */
    private function createTopologies(): void
    {
        $topology = new Topology();
        $topology
            ->setName('file')
            ->setRawBpmn($this->load('file.tplg'))
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->persist($topology);

        $topology3 = new Topology();
        $topology3
            ->setName('file2')
            ->setRawBpmn($this->load('file2.tplg', FALSE))
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->persist($topology3);

        $topology2 = new Topology();
        $topology2
            ->setName('old-file')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->persist($topology2);
        $this->dm->flush();
    }

    /**
     * @param string $name
     * @param bool   $change
     *
     * @return string
     */
    private function load(string $name, bool $change = TRUE): string
    {
        $content = file_get_contents(sprintf('%s/data/%s', __DIR__, $name));

        if (!$change) {
            return $content;
        }

        return str_replace('salesforce-create-contact-mapper', 'salesforce-updaet-contact-mapper', $content);
    }

}