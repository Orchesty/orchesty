<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Interface ConnectorInterface
 *
 * @package Hanaboso\PipesFramework\Connector
 */
interface ConnectorInterface
{

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto;

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto;

}
