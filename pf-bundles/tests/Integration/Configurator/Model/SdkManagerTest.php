<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\SdkManager;
use Hanaboso\PipesFramework\Configurator\Repository\SdkRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class SdkManagerTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Model
 */
#[CoversClass(SdkManager::class)]
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
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $this->createSdk('One');
        $this->createSdk('Two');

        $data = $this->manager->getAll();

        self::assertCount(2, $data);
        self::assertSame('One', $data[0]->getName());
        self::assertSame('One', $data[0]->getUrl());
        self::assertSame('Two', $data[1]->getName());
        self::assertSame('Two', $data[1]->getUrl());
    }

    /**
     * @throws Exception
     */
    public function testGetOne(): void
    {
        $data = $this->manager->getOne($this->createSdk('One')->getId());

        self::assertSame('One', $data->getName());
        self::assertSame('One', $data->getUrl());

        self::expectException(DocumentNotFoundException::class);
        $this->manager->getOne('Unknown');
    }

    /**
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

        self::assertSame('url', $data->getUrl());
        self::assertSame('Name', $data->getName());
    }

    /**
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

        self::assertSame('Name1', $data->getName());
        self::assertSame('url1', $data->getUrl());
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $data = $this->manager->delete($this->createSdk('One'));

        $this->dm->clear();

        self::assertCount(0, $this->sdkRepo->findAll());

        self::assertSame('One', $data->getName());
        self::assertSame('One', $data->getUrl());
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
