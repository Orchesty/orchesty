<?php declare(strict_types=1);

namespace Tests\Integration\HbPFMapperBundle\loader;

use Hanaboso\PipesFramework\HbPFMapperBundle\Loader\MapperLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class MapperLoaderTest
 *
 * @package Tests\Integration\HbPFMapperBundle\loader
 */
final class MapperLoaderTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetAllMappers(): void
    {
        $connector = new MapperLoader($this->ownContainer);

        $fields = $connector->getAllMappers();
        self::assertCount(3, $fields);

        $fields = $connector->getAllMappers(['null']);
        self::assertCount(2, $fields);
    }

}