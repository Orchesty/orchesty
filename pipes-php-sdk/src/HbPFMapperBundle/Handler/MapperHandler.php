<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler;

use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Loader\MapperLoader;

/**
 * Class MapperHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler
 */
class MapperHandler
{

    /**
     * @var MapperLoader
     */
    private MapperLoader $mapperLoader;

    /**
     * MapperHandler constructor.
     *
     * @param MapperLoader $mapperLoader
     */
    public function __construct(MapperLoader $mapperLoader)
    {
        $this->mapperLoader = $mapperLoader;
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws MapperException
     */
    public function process(string $id, array $data): array
    {
        return $this->mapperLoader->loadMapper($id)->process($data);
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws MapperException
     */
    public function processTest(string $id, array $data): array
    {
        $this->mapperLoader->loadMapper($id);

        return $data;
    }

    /**
     * @return mixed[]
     */
    public function getMappers(): array
    {
        return $this->mapperLoader->getAllMappers();
    }

}
