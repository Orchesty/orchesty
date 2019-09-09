<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationInterface;
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

    /**
     * @param ApplicationInterface $application
     *
     * @return ConnectorInterface
     */
    public function setApplication(ApplicationInterface $application): ConnectorInterface;

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string;

}
