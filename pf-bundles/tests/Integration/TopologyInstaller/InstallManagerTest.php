<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\TopologyInstaller;

use Exception;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\TopologyInstaller\CategoryParser;
use Hanaboso\PipesFramework\TopologyInstaller\InstallManager;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use PHPUnit\Framework\MockObject\MockObject;
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
     *
     * @throws Exception
     */
    public function testPrepareInstall(): void
    {
        $this->createTopologies();
        $manager = $this->getManager();
        $output  = $manager->prepareInstall(TRUE, TRUE, TRUE);
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
     *
     * @throws Exception
     */
    public function testMakeInstall(): void
    {
        $this->dm->getClient()->dropDatabase('pipes');
        $this->createTopologies();
        $manager = $this->getManager();
        $manager->prepareInstall(TRUE, TRUE, TRUE);
        $res = $this->redis->get(InstallManager::AUTO_INSTALL_KEY);
        self::assertNotEmpty($res);

        $output = $manager->makeInstall(TRUE, TRUE, TRUE);
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
     *
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
        /** @var MockObject|TopologyGeneratorBridge $requestHandler */
        $requestHandler = self::createMock(TopologyGeneratorBridge::class);
        $requestHandler->method('runTopology')->willReturn(new ResponseDto(200, '', '', []));
        $requestHandler->method('deleteTopology')->willReturn(new ResponseDto(200, '', '', []));

        $this->redis     = new Client(Parameters::create((string) getenv('REDIS_DSN')));
        $topologyManager = self::$container->get('hbpf.configurator.manager.topology');
        $dir             = sprintf('%s/data', __DIR__);
        $categoryManager = self::$container->get('hbpf.configurator.manager.category');
        $categoryParser  = new CategoryParser($this->dm, $categoryManager);
        $categoryParser->addRoot('systems', $dir);

        $xmlDecoder = self::$container->get('rest.decoder.xml');

        return new InstallManager(
            $this->dm,
            $topologyManager,
            $requestHandler,
            $categoryParser,
            $xmlDecoder,
            (string) getenv('REDIS_DSN'),
            [$dir]
        );
    }

    /**
     * @throws Exception
     */
    private function createTopologies(): void
    {
        $xmlDecoder = self::$container->get('rest.decoder.xml');
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
                                TRUE
                            )
                        )
                    )
                )
            )
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
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
                                FALSE
                            )
                        )
                    )
                )
            )
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
