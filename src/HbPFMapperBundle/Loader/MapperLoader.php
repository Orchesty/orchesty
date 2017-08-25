<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMapperBundle\Loader;

use Hanaboso\PipesFramework\HbPFMapperBundle\Exception\MapperException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MapperLoader
 *
 * @package Hanaboso\PipesFramework\HbPFMapperBundle\Loader
 */
class MapperLoader
{

    public const PREFIX = 'hbpf.mapper.';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * MapperLoader constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $id
     *
     * @return object
     * @throws MapperException
     */
    public function loadMapper(string $id)
    {
        $name = sprintf('%s%s', MapperLoader::PREFIX, $id);
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }

        throw new MapperException(
            sprintf('Mapper \'%s\' not exist', $name),
            MapperException::MAPPER_NOT_EXIST
        );
    }

}