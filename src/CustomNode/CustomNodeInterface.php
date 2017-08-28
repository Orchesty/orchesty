<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode;

/**
 * Interface CustomNodeInterface
 *
 * @package Hanaboso\PipesFramework\CustomNode
 */
interface CustomNodeInterface
{

    /**
     * @param array $data
     *
     * @return string[]
     */
    public function process(array $data): array;

}