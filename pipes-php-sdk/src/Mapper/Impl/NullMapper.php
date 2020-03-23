<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Mapper\Impl;

use Hanaboso\PipesPhpSdk\Mapper\MapperAbstract;

/**
 * Class NullMapper
 *
 * @package Hanaboso\PipesPhpSdk\Mapper\Impl
 */
final class NullMapper extends MapperAbstract
{

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function process(array $data): array
    {
        return $data;
    }

}
