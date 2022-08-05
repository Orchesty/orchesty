<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class CommonNodeAbstract
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode
 */
abstract class CommonNodeAbstract implements CommonNodeInterface
{

    use CommonNodeTrait;

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    abstract function processAction(ProcessDto $dto): ProcessDto;

}
