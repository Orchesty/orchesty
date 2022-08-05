<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class ConnectorException
 *
 * @package Hanaboso\PipesPhpSdk\Connector\Exception
 */
final class ConnectorException extends PipesFrameworkExceptionAbstract
{

    public const CONNECTOR_SERVICE_NOT_FOUND = self::OFFSET + 1;

    protected const OFFSET = 1_000;

}
