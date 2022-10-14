<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;

/**
 * Class TestNullCustomNode
 *
 * @package PipesPhpSdkTests\Unit\CustomNode
 */
final class TestNullCustomNode extends CommonNodeAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'null-test-custom-node';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dto;

        return new ProcessDto();
    }

}
