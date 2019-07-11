<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode\Impl;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeInterface;

/**
 * Class Sleep01CustomNode
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode\Impl
 */
class Sleep01CustomNode implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        usleep(100000);

        return $dto;
    }

}