<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode\Impl;

use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class NullCustomNode
 *
 * @package Hanaboso\PipesFramework\CustomNode\Impl
 */
class NullCustomNode implements CustomNodeInterface
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