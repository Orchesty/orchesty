<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode\Impl;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class Sleep01CustomNode
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode\Impl
 */
class Sleep01CustomNode extends CustomNodeAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        usleep(100_000);

        return $dto;
    }

}
