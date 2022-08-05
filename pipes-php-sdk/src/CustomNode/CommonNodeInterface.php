<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Interface CommonNodeInterface
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode
 */
interface CommonNodeInterface
{

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto;

}
