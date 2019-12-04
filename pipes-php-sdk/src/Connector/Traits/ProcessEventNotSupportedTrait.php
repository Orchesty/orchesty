<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector\Traits;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Trait ProcessEventNotSupportedTrait
 *
 * @package Hanaboso\PipesPhpSdk\Connector\Traits
 */
trait ProcessEventNotSupportedTrait
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $dto;

        throw new ConnectorException(
            sprintf('Method %s::%s is not supported!', static::class, __FUNCTION__),
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

}
