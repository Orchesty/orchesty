<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode\Impl;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class MSleep05CustomNode
 *
 * @package Hanaboso\PipesFramework\CustomNode\Impl
 */
class Sleep05CustomNode implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        usleep(500000);

        return $dto;
    }

}