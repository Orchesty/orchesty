<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Interface CustomNodeInterface
 *
 * @package Hanaboso\PipesFramework\CustomNode
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