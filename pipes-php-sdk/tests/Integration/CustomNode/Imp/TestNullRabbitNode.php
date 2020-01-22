<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\CustomNode\Imp;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode;

/**
 * Class TestNullRabbitNode
 *
 * @package PipesPhpSdkTests\Integration\CustomNode\Imp
 */
final class TestNullRabbitNode extends RabbitCustomNode
{

    /**
     * @param ProcessDto $dto
     */
    protected function processBatch(ProcessDto $dto): void
    {
        $dto;
    }

}
