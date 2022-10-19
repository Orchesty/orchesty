<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Storage\DataStorage;

use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\DataStorageManager;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\Document\DataStorageDocument;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class DataStorageManagerTest
 *
 * @package PipesPhpSdkTests\Integration\Storage\DataStorage
 */
final class DataStorageManagerTest extends KernelTestCaseAbstract
{

    /**
     * @var DataStorageManager $dataStorageManager
     */
    private DataStorageManager $dataStorageManager;

    /**
     * @var DatabaseManagerLocator $dml
     */
    private DatabaseManagerLocator $dml;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Storage\DataStorage\DataStorageManager::store
     * @covers \Hanaboso\PipesPhpSdk\Storage\DataStorage\DataStorageManager::load
     * @covers \Hanaboso\PipesPhpSdk\Storage\DataStorage\DataStorageManager::remove
     *
     * @throws Exception
     */
    public function testSaveLoadAndRemove(): void
    {
        $this->dml->getDm()?->getDocumentCollection(DataStorageDocument::class)->drop();

        $this->dataStorageManager->store('1', ['d1', 'd2'], 'a1', 'u1');
        $this->dataStorageManager->store('2', ['d1', 'd2'], 'a2', 'u2');

        $data1 = (new DataStorageDocument())
            ->setProcessId('1')
            ->setData('d1')
            ->setApplication('a1')
            ->setUser('u1');
        $data2 = (new DataStorageDocument())
            ->setProcessId('1')
            ->setData('d2')
            ->setApplication('a1')
            ->setUser('u1');

        /** @var DataStorageDocument[] $entities */
        $entities = $this->dataStorageManager->load('1', 'a1', 'u1');
        $data1->setId($entities[0]->getId());
        $data2->setId($entities[1]->getId());
        self::assertEquals([$data1, $data2], $entities);

        $entities = $this->dataStorageManager->load('1', 'a1', 'u1', 1, 1);
        self::assertEquals([$data2], $entities);

        $this->dataStorageManager->remove('1', 'a1', 'u1');
        $entities = $this->dataStorageManager->load('2');
        self::assertEquals(2, sizeof($entities ?? []));
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dml                = self::getContainer()->get('hbpf.database_manager_locator');
        $this->dataStorageManager = new DataStorageManager($this->dml);
    }

}
