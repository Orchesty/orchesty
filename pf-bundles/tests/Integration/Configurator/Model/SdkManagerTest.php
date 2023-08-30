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
        self::assertEquals('One', $data[0]->getKey());
        self::assertEquals('One', $data[0]->getValue());
        self::assertEquals('Two', $data[1]->getKey());
        self::assertEquals('Two', $data[1]->getValue());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::getOne
     *
     * @throws Exception
     */
    public function testGetOne(): void
    {
        $data = $this->manager->getOne($this->createSdk('One')->getId());

        self::assertEquals('One', $data->getKey());
        self::assertEquals('One', $data->getValue());

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
                Sdk::KEY   => 'Key',
                Sdk::VALUE => 'Value',
            ],
        );

        $this->dm->clear();
        self::assertCount(1, $this->sdkRepo->findAll());

        self::assertEquals('Key', $data->getKey());
        self::assertEquals('Value', $data->getValue());
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
                Sdk::KEY   => 'Key',
                Sdk::VALUE => 'Value',
            ],
        );

        $this->dm->clear();
        self::assertCount(1, $this->sdkRepo->findAll());

        self::assertEquals('Key', $data->getKey());
        self::assertEquals('Value', $data->getValue());
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

        self::assertEquals('One', $data->getKey());
        self::assertEquals('One', $data->getValue());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::$container->get('hbpf.configurator.manager.sdk');
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
            ->setKey($string)
            ->setValue($string);

        $this->dm->persist($sdk);
        $this->dm->flush();

        return $sdk;
    }

}