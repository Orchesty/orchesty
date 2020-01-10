<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class ConnectorException
 *
 * @package Hanaboso\PipesPhpSdk\Connector\Exception
 */
final class ConnectorException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 1_000;

    public const CONNECTOR_SERVICE_NOT_FOUND              = self::OFFSET + 1;
    public const CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT    = self::OFFSET + 2;
    public const CONNECTOR_FAILED_TO_PROCESS              = self::OFFSET + 3;
    public const CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION   = self::OFFSET + 4;
    public const CONNECTOR_DOES_NOT_HAVE_PROCESS_BATCH    = self::OFFSET + 5;
    public const INVALID_SETTING                          = self::OFFSET + 6;
    public const CUSTOM_NODE_DOES_NOT_HAVE_PROCESS_ACTION = self::OFFSET + 7;

}
