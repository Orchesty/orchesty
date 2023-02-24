<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\TopologyInstaller;

use Exception;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\TopologyInstaller\Cache\RedisCache;
use Hanaboso\PipesFramework\TopologyInstaller\CategoryParser;
use Hanaboso\PipesFramework\TopologyInstaller\InstallManager;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\Utils\File\File;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Predis\Client;
use Predis\Connection\Parameters;

/**
 * Class InstallManagerTest
 *
 * @package PipesFrameworkTests\Integration\TopologyInstaller
 */
final class InstallManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var Client<mixed>
     */
    private Client $redis;

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::prepareInstall
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::generateOutput
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto::toArray
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto::getArrayFromFiles
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto::getArrayFromTopologies
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto::getArrayFromObject
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::getName
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::getPath
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::getFileContents
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject::getTopology
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject::getFile
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\TopologiesComparator::compare
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\TopologiesComparator::prepareFiles
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\RedisCache
     *
     * @throws Exception
     */
    public function testPrepareInstall(): void
    {
        $this->createTopologies();
        $manager = $this->getManager();
        $output  = $manager->prepareInstall(TRUE, TRUE, TRUE, 'worker');
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
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::prepareInstall
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::generateOutput
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto::toArray
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto::getArrayFromFiles
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto::getArrayFromTopologies
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto::getArrayFromObject
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::getName
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::getPath
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::getFileContents
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::from
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject::getTopology
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\UpdateObject::getFile
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeInstall
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeCreate
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeRunnable
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeUpdate
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeDelete
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\RedisCache
     *
     * @throws Exception
     */
    public function testMakeInstall(): void
    {
        $this->dm->getClient()->dropDatabase('pipes');
        $this->createTopologies();
        $manager = $this->getManager();
        $manager->prepareInstall(TRUE, TRUE, TRUE, '');
        $res = $this->redis->get(InstallManager::AUTO_INSTALL_KEY);
        self::assertNotEmpty($res);

        $output = $manager->makeInstall(TRUE, TRUE, TRUE, 'worker');
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
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\InstallManager::makeInstall
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Cache\RedisCache
     *
     * @throws Exception
     */
    public function testMakeInstallEx(): void
    {
        $manager = $this->getManager();
        $output  = $manager->makeInstall(TRUE, TRUE, TRUE, '');
        self::assertArrayHasKey('create', $output);
        self::assertArrayHasKey('update', $output);
        self::assertArrayHasKey('delete', $output);
    }

    /**
     * @return InstallManager
     * @throws Exception
     */
    private function getManager(): InstallManager
    {
        $requestHandler = self::createMock(TopologyGeneratorBridge::class);
        $requestHandler->method('runTopology')->willReturn(new ResponseDto(200, '', '', []));
        $requestHandler->method('deleteTopology')->willReturn(new ResponseDto(200, '', '', []));

        $redisDsn        = 'redis://redis:6379';
        $this->redis     = new Client(Parameters::create($redisDsn));
        $topologyManager = self::getContainer()->get('hbpf.configurator.manager.topology');
        $dir             = sprintf('%s/data', __DIR__);
        $categoryManager = self::getContainer()->get('hbpf.configurator.manager.category');
        $categoryParser  = new CategoryParser($this->dm, $categoryManager);
        $categoryParser->addRoot('systems', $dir);

        $xmlDecoder = self::getContainer()->get('rest.decoder.xml');
        $redisCache = new RedisCache($redisDsn);

        return new InstallManager(
            $this->dm,
            $topologyManager,
            $requestHandler,
            $categoryParser,
            $xmlDecoder,
            $redisCache,
            [$dir],
            TRUE,
        );
    }

    /**
     * @throws Exception
     */
    private function createTopologies(): void
    {
        $xmlDecoder = self::getContainer()->get('rest.decoder.xml');
        $topology   = new Topology();
        $topology
            ->setName('file')
            ->setRawBpmn($this->load('file.tplg', TRUE))
            ->setContentHash(
                TopologySchemaUtils::getIndexHash(
                    TopologySchemaUtils::getSchemaObject(
                        $xmlDecoder->decode(
                            $this->load(
                                'file.tplg',
                                TRUE,
                            ),
                        ),
                    ),
                ),
            )
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);
        $this->dm->persist($topology);

        $topology3 = new Topology();
        $topology3
            ->setName('file2')
            ->setRawBpmn($this->load('file2.tplg', FALSE))
            ->setContentHash(
                TopologySchemaUtils::getIndexHash(
                    TopologySchemaUtils::getSchemaObject(
                        $xmlDecoder->decode(
                            $this->load(
                                'file2.tplg',
                                FALSE,
                            ),
                        ),
                    ),
                ),
            )
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);
        $this->dm->persist($topology3);

        $topology2 = new Topology();
        $topology2
            ->setName('old-file')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);
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
        $content = File::getContent(sprintf('%s/data/%s', __DIR__, $name));

        if (!$change) {
            return $content;
        }

        return str_replace('salesforce-create-contact-mapper', 'salesforce-updaet-contact-mapper', $content);
    }

}
