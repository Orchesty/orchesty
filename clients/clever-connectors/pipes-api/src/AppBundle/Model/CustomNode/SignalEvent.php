<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CustomNode;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class SignalEvent
 *
 * @package CleverConnectors\AppBundle\Model\CustomNode
 */
class SignalEvent implements CustomNodeInterface
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