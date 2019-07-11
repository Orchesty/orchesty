<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Mapper\Impl;

use Hanaboso\PipesPhpSdk\Mapper\MapperInterface;

/**
 * Class NullMapper
 *
 * @package Hanaboso\PipesPhpSdk\Mapper\Impl
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