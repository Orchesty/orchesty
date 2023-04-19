<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\SdkManager;
use Hanaboso\PipesFramework\Configurator\Repository\SdkRepository;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class SdkManagerTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Model
 */
final class SdkManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var SdkManager
     */
    private SdkManager $manager;

    /**
     * @var ObjectRepository<Sdk>&SdkRepository
     */
    private SdkRepository $sdkRepo;

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::getAll
     *
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $this->createSdk('One');
        $this->createSdk('Two');

        $data = $this->manager->getAll();

        self::assertCount(2, $data);
        self::assertEquals('One', $data[0]->getName());
        self::assertEquals('One', $data[0]->getUrl());
        self::assertEquals('Two', $data[1]->getName());
        self::assertEquals('Two', $data[1]->getUrl());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::getOne
     *
     * @throws Exception
     */
    public function testGetOne(): void
    {
        $data = $this->manager->getOne($this->createSdk('One')->getId());

        self::assertEquals('One', $data->getName());
        self::assertEquals('One', $data->getUrl());

        self::expectException(DocumentNotFoundException::class);
        $this->manager->getOne('Unknown');
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::create
     *
     * @throws Exception
     */
    public function testCreate(): void
    {
        $data = $this->manager->create(
            [
                Sdk::HEADERS => [],
                Sdk::NAME => 'Name',
                Sdk::URL  => 'url',
            ],
        );

        $this->dm->clear();
        self::assertCount(1, $this->sdkRepo->findAll());

        self::assertEquals('url', $data->getUrl());
        self::assertEquals('Name', $data->getName());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::update
     *
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $data = $this->manager->update(
            $this->createSdk('One'),
            [
                Sdk::NAME => 'Name1',
                Sdk::URL  => 'url1',
            ],
        );

        $this->dm->clear();
        self::assertCount(1, $this->sdkRepo->findAll());

        self::assertEquals('Name1', $data->getName());
        self::assertEquals('url1', $data->getUrl());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::delete
     *
     * @throws Exception
     */
    public function testDelete(): void
    {
        $data = $this->manager->delete($this->createSdk('One'));

        $this->dm->clear();

        self::assertCount(0, $this->sdkRepo->findAll());

        self::assertEquals('One', $data->getName());
        self::assertEquals('One', $data->getUrl());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::getContainer()->get('hbpf.configurator.manager.sdk');
        $this->sdkRepo = $this->dm->getRepository(Sdk::class);
    }

    /**
     * @param string $string
     *
     * @return Sdk
     * @throws Exception
     */
    private function createSdk(string $string): Sdk
    {
        $sdk = (new Sdk())
            ->setName($string)
            ->setUrl($string);

        $this->dm->persist($sdk);
        $this->dm->flush();

        return $sdk;
    }

}
