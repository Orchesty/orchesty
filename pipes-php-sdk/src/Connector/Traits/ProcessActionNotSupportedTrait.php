<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector\Traits;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Trait ProcessActionNotSupportedTrait
 *
 * @package Hanaboso\PipesPhpSdk\Connector\Traits
 */
trait ProcessActionNotSupportedTrait
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dto;

        throw new ConnectorException(
            sprintf('Method %s::%s is not supported!', static::class, __FUNCTION__),
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION,
        );
    }

}
