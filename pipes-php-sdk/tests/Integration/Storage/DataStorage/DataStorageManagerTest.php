<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Storage\DataStorage;

use Exception;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\DataStorageManager;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\Document\DataStorageDocument;
use Hanaboso\PipesPhpSdk\Storage\File\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class DataStorageManagerTest
 *
 * @package PipesPhpSdkTests\Integration\Storage\DataStorage
 */
#[CoversClass(DataStorageManager::class)]
final class DataStorageManagerTest extends KernelTestCaseAbstract
{

    /**
     * @var DataStorageManager $dataStorageManager
     */
    private DataStorageManager $dataStorageManager;

    /**
     * @var FileSystem $fileSystem
     */
    private FileSystem $fileSystem;

    /**
     * @throws Exception
     */
    public function testSaveLoadAndRemove(): void
    {
        $this->fileSystem->delete('1');
        $this->fileSystem->delete('2');

        $this->dataStorageManager->store('1', ['d1', 'd2'], 'a1', 'u1');
        $this->dataStorageManager->store('2', ['d1', 'd2'], 'a2', 'u2');

        $data1 = (new DataStorageDocument())
            ->setData('d1')
            ->setApplication('a1')
            ->setUser('u1');
        $data2 = (new DataStorageDocument())
            ->setData('d2')
            ->setApplication('a1')
            ->setUser('u1');

        /** @var DataStorageDocument[] $entities */
        $entities = $this->dataStorageManager->load('1', 'a1', 'u1');
        self::assertEquals($data1->getApplication(), $entities[0]->getApplication());
        self::assertEquals($data1->getUser(), $entities[0]->getUser());
        self::assertEquals($data1->getData(), $entities[0]->getData());

        self::assertEquals($data2->getApplication(), $entities[1]->getApplication());
        self::assertEquals($data2->getUser(), $entities[1]->getUser());
        self::assertEquals($data2->getData(), $entities[1]->getData());

        $entities = $this->dataStorageManager->load('1', 'a1', 'u1', 1, 1);
        self::assertEquals($data2->getApplication(), $entities[0]->getApplication());
        self::assertEquals($data2->getUser(), $entities[0]->getUser());
        self::assertEquals($data2->getData(), $entities[0]->getData());

        $this->dataStorageManager->remove('1', 'a1', 'u1');
        $entities = $this->dataStorageManager->load('2');
        self::assertEquals(2, sizeof($entities));
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystem         = new FileSystem();
        $this->dataStorageManager = new DataStorageManager($this->fileSystem);
    }

}
