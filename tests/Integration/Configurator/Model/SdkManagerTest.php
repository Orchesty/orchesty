<?php declare(strict_types=1);

namespace Tests\Integration\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\SdkManager;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SdkManagerTest
 *
 * @package Tests\Integration\Configurator\Model
 */
final class SdkManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var SdkManager
     */
    private $manager;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = self::$container->get('hbpf.configurator.manager.sdk');
    }

    /**
     * @covers SdkManager::getAll
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
     * @throws Exception
     *
     * @covers SdkManager::getOne
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
     * @covers SdkManager::create
     */
    public function testCreate(): void
    {
        $data = $this->manager->create(
            [
                Sdk::KEY   => 'Key',
                Sdk::VALUE => 'Value',
            ]
        );

        $this->dm->clear();
        self::assertCount(1, $this->dm->getRepository(Sdk::class)->findAll());

        self::assertEquals('Key', $data->getKey());
        self::assertEquals('Value', $data->getValue());
    }

    /**
     * @covers SdkManager::update
     */
    public function testUpdate(): void
    {
        $data = $this->manager->update(
            $this->createSdk('One'),
            [
                Sdk::KEY   => 'Key',
                Sdk::VALUE => 'Value',
            ]
        );

        $this->dm->clear();
        self::assertCount(1, $this->dm->getRepository(Sdk::class)->findAll());

        self::assertEquals('Key', $data->getKey());
        self::assertEquals('Value', $data->getValue());
    }

    /**
     * @covers SdkManager::delete
     */
    public function testDelete(): void
    {
        $data = $this->manager->delete($this->createSdk('One'));

        $this->dm->clear();
        self::assertCount(0, $this->dm->getRepository(Sdk::class)->findAll());

        self::assertEquals('One', $data->getKey());
        self::assertEquals('One', $data->getValue());
    }

    /**
     * @param string $string
     *
     * @return Sdk
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
