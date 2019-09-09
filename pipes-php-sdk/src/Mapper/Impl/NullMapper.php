<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Mapper\Impl;

use Hanaboso\PipesPhpSdk\Mapper\MapperAbstract;

/**
 * Class NullMapper
 *
 * @package Hanaboso\PipesPhpSdk\Mapper\Impl
 */
class NullMapper extends MapperAbstract
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