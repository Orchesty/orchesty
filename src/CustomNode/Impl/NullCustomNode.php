<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode\Impl;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class NullCustomNode
 *
 * @package Hanaboso\PipesFramework\CustomNode\Impl
 */
class NullCustomNode implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }

}