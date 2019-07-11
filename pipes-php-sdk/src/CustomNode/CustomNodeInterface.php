<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Interface CustomNodeInterface
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode
 */
interface CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto;

}