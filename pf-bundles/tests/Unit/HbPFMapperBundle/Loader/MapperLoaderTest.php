<?php declare(strict_types=1);

namespace Tests\Unit\HbPFMapperBundle\Loader;

use Exception;
use Hanaboso\PipesFramework\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesFramework\HbPFMapperBundle\Loader\MapperLoader;
use Hanaboso\PipesFramework\Mapper\Impl\NullMapper;
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
        $this->mapperLoader = $this->ownContainer->get('hbpf.mapper.loader.mapper');
    }

    /**
     * @throws Exception
     */
    public function testLoadMapper(): void
    {
        $mapper = $this->mapperLoader->loadMapper('null');

        $this->assertInstanceOf(NullMapper::class, $mapper);
    }

    /**
     * @throws Exception
     */
    public function testLoadMissingMapper(): void
    {
        $this->expectException(MapperException::class);
        $this->expectExceptionCode(MapperException::MAPPER_NOT_EXIST);

        $this->mapperLoader->loadMapper('missing');
    }

}