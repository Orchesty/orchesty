<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mapper;

/**
 * Interface MapperInterface
 *
 * @package Hanaboso\PipesFramework\Mapper
 */
interface MapperInterface
{

    /**
     * @param array $data
     *
     * @return array
     */
    public function process(array $data): array;

}