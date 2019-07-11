<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode\Impl;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeInterface;

/**
 * Class Sleep02CustomNode
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode\Impl
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
