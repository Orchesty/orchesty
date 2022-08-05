<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Interface ConnectorInterface
 *
 * @package Hanaboso\PipesPhpSdk\Connector
 */
interface ConnectorInterface
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
