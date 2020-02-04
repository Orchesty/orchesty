<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\HbPFMapperBundle\loader;

use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Loader\MapperLoader;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class MapperLoaderTest
 *
 * @package PipesFrameworkTests\Integration\HbPFMapperBundle\loader
 */
final class MapperLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Loader\MapperLoader::getAllMappers
     */
    public function testGetAllMappers(): void
    {
        $connector = new MapperLoader(self::$container);

        $fields = $connector->getAllMappers();
        self::assertCount(3, $fields);

        $fields = $connector->getAllMappers(['null']);
        self::assertCount(2, $fields);
    }

}
