<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode\Impl;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class MSleep02CustomNode
 *
 * @package Hanaboso\PipesFramework\CustomNode\Impl
 */
class Sleep02CustomNode implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        usleep(200000);

        return $dto;
    }

}