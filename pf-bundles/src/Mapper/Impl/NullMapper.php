<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mapper\Impl;

use Hanaboso\PipesFramework\Mapper\MapperInterface;

/**
 * Class NullMapper
 *
 * @package Hanaboso\PipesFramework\Mapper\Impl
 */
class NullMapper implements MapperInterface
{

    /**
     * @param array $data
     *
     * @return array
     */
    public function process(array $data): array
    {
        return $data;
    }

}