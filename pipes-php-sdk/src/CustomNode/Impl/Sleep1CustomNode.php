<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode\Impl;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class Sleep1CustomNode
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode\Impl
 */
final class Sleep1CustomNode extends CustomNodeAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        sleep(1);

        return $dto;
    }

}
