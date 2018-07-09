<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMapperBundle\Handler;

use Hanaboso\PipesFramework\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesFramework\HbPFMapperBundle\Loader\MapperLoader;

/**
 * Class MapperHandler
 *
 * @package Hanaboso\PipesFramework\HbPFMapperBundle\Handler
 */
class MapperHandler
{

    /**
     * @var MapperLoader
     */
    private $mapperLoader;

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
     * @param string $id
     * @param array  $data
     *
     * @return array
     * @throws MapperException
     */
    public function process(string $id, array $data): array
    {
        return $this->mapperLoader->loadMapper($id)->process($data);
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     * @throws MapperException
     */
    public function processTest(string $id, array $data): array
    {
        $this->mapperLoader->loadMapper($id);

        return $data;
    }

}