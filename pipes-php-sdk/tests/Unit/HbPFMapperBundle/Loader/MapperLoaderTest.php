<?php declare(strict_types=1);

namespace Tests\Unit\HbPFMapperBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Loader\MapperLoader;
use Hanaboso\PipesPhpSdk\Mapper\Impl\NullMapper;
use Tests\KernelTestCaseAbstract;

/**
 * Class MapperLoaderTest
 *
 * @package Tests\Unit\HbPFMapperBundle\Loader
 */
final class MapperLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var MapperLoader
     */
    private $mapperLoader;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mapperLoader = self::$container->get('hbpf.mapper.loader.mapper');
    }

    /**
     * @throws Exception
     */
    public function testLoadMapper(): void
    {
        $mapper = $this->mapperLoader->loadMapper('null');

        self::assertInstanceOf(NullMapper::class, $mapper);
    }

    /**
     * @throws Exception
     */
    public function testLoadMissingMapper(): void
    {
        self::expectException(MapperException::class);
        self::expectExceptionCode(MapperException::MAPPER_NOT_EXIST);

        $this->mapperLoader->loadMapper('missing');
    }

}
