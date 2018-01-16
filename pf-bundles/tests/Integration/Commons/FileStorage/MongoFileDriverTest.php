<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/21/17
 * Time: 12:10 PM
 */

namespace Tests\Integration\Commons\FileStorage;

use Hanaboso\PipesFramework\Commons\Exception\FileStorageException;
use Hanaboso\PipesFramework\Commons\FileStorage\Driver\FileMongo;
use Hanaboso\PipesFramework\Commons\FileStorage\Driver\MongoFileDriver;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class MongoFileDriverTest
 *
 * @package Tests\Integration\Commons\FileStorage
 */
class MongoFileDriverTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers MongoFileDriver::save()
     * @covers MongoFileDriver::get()
     * @covers MongoFileDriver::delete()
     * @covers MongoFileDriver::generatePath()
     */
    public function testFileStorage(): void
    {
        /** @var MongoFileDriver $driver */
        $driver = $this->container->get('hbpf.file_storage.driver.mongo');

        $res = $driver->save('test_content', 'test_name');
        $this->dm->clear();

        /** @var FileMongo $file */
        $fileContent = $driver->get($res->getUrl());
        self::assertEquals('test_content', $fileContent);

        $this->dm->clear();
        $driver->delete($res->getUrl());
        $this->expectException(FileStorageException::class);
        $this->expectExceptionCode(FileStorageException::FILE_NOT_FOUND);
        $driver->get($res->getUrl());
    }

}