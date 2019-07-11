<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Mapper;

/**
 * Interface MapperInterface
 *
 * @package Hanaboso\PipesPhpSdk\Mapper
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