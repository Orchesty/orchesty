<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Storage\File;

use Exception;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\Document\DataStorageDocument;
use Hanaboso\PipesPhpSdk\Storage\File\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class FileSystemTest
 *
 * @package PipesPhpSdkTests\Integration\Storage\File
 */
#[CoversClass(FileSystem::class)]
final class FileSystemTest extends KernelTestCaseAbstract
{

    /**
     * @var FileSystem $fileSystem
     */
    private FileSystem $fileSystem;

    /**
     * @throws Exception
     */
    public function testSaveLoadAndRemove(): void
    {
        $dataStorageDocument = (new DataStorageDocument())
            ->setUser('testUser')
            ->setApplication('testApplication')
            ->setData(['a' => ['b' => 'c']]);

        self::assertEquals(TRUE, $this->fileSystem->write('testFile', [$dataStorageDocument]));
        $data = $this->fileSystem->read('testFile');
        self::assertEquals($data[0], $dataStorageDocument);
        self::assertEquals(TRUE,$this->fileSystem->delete('testFile'));
    }

    /**
     * @throws Exception
     */
    public function testGetFilePath(): void
    {
        self::assertEquals('/tmp/orchesty/data/testId.json',$this->fileSystem->getFilePath('testId'));
        self::assertEquals('/tmp/orchesty/tmp/testId.json',$this->fileSystem->getFilePath('testId', TRUE));
    }

    /**
     * @throws Exception
     */
    public function testGetDirectoryPath(): void
    {
        self::assertEquals('/tmp/orchesty/data',$this->fileSystem->getDirectoryPath());
        self::assertEquals('/tmp/orchesty/tmp',$this->fileSystem->getDirectoryPath(TRUE));
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystem = new FileSystem();
    }

}
