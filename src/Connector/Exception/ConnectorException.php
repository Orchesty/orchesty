<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Connector\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;

/**
 * Class ConnectorException
 *
 * @package Hanaboso\PipesFramework\Connector\Exception
 */
final class ConnectorException extends PipesFrameworkException
{

    protected const OFFSET = 1000;

    public const CONNECTOR_SERVICE_NOT_FOUND            = self::OFFSET + 1;
    public const CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT  = self::OFFSET + 2;
    public const CONNECTOR_FAILED_TO_PROCESS            = self::OFFSET + 3;
    public const CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION = self::OFFSET + 4;
    public const CONNECTOR_DOES_NOT_HAVE_PROCESS_BATCH  = self::OFFSET + 5;
    public const INVALID_SETTING                        = self::OFFSET + 6;

}
