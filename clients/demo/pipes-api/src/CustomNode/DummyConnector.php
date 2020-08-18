<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class DummyConnector
 *
 * @package Demo\CustomNode
 */
final class DummyConnector extends CustomNodeAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }

}
