<?php declare(strict_types=1);

namespace Tests\Integration\TopologyInstaller;

use Exception;
use FOS\RestBundle\Decoder\XmlDecoder;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\RequestHandler;
use Hanaboso\PipesFramework\TopologyInstaller\CategoryParser;
use Hanaboso\PipesFramework\TopologyInstaller\InstallManager;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Client;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class InstallManagerTest
 *
 * @package Tests\Integration\TopologyInstaller
 */
final class InstallManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var Client
     */
    private $redis;

    /**
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public function testMakeInstallEx(): void
    {
        $manager = $this->getManager();
        $this->expectException(ConnectorException::class);
        $manager->makeInstall(TRUE, TRUE, TRUE);
    }

    /**
     * @return InstallManager
     * @throws Exception
     */
    private function getManager(): InstallManager
    {
        /** @var MockObject|RequestHandler $requestHandler */
        $requestHandler = self::createMock(RequestHandler::class);
        $requestHandler->method('runTopology')->willReturn(new ResponseDto(200, '', '', []));
        $requestHandler->method('deleteTopology')->willReturn(new ResponseDto(200, '', '', []));

        $this->redis     = self::$container->get('snc_redis.default');
        $topologyManager = self::$container->get('hbpf.configurator.manager.topology');
        $dir             = sprintf('%s/data', __DIR__);
        $categoryManager = self::$container->get('hbpf.configurator.manager.category');
        $categoryParser  = new CategoryParser($this->dm, $categoryManager);
        $categoryParser->addRoot('systems', $dir);

        return new InstallManager($this->dm, $this->redis, $topologyManager, $requestHandler, $categoryParser, [$dir]);
    }

    /**
     * @throws Exception
     */
    private function createTopologies(): void
    {
        $xmlDecoder = new XmlDecoder();

        $topology = new Topology();
        $topology
            ->setName('file')
            ->setRawBpmn($this->load('file.tplg', TRUE))
            ->setContentHash(TopologySchemaUtils::getIndexHash(TopologySchemaUtils::getSchemaObject($xmlDecoder->decode($this->load('file.tplg',
                TRUE)))))
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->persist($topology);

        $topology3 = new Topology();
        $topology3
            ->setName('file2')
            ->setRawBpmn($this->load('file2.tplg', FALSE))
            ->setContentHash(TopologySchemaUtils::getIndexHash(TopologySchemaUtils::getSchemaObject($xmlDecoder->decode($this->load('file2.tplg',
                FALSE)))))
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
    private function load(string $name, bool $change): string
    {
        $content = (string) file_get_contents(sprintf('%s/data/%s', __DIR__, $name));

        if (!$change) {
            return $content;
        }

        return str_replace('salesforce-create-contact-mapper', 'salesforce-updaet-contact-mapper', $content);
    }

}
