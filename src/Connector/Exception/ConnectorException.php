<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 10:41 AM
 */

namespace Hanaboso\PipesFramework\Connector\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class ConnectorException
 *
 * @package Hanaboso\PipesFramework\Connector\Exception
 */
class ConnectorException extends PipesFrameworkException
{

    protected const OFFSET = 1000;

    public const CONNECTOR_SERVICE_NOT_FOUND           = self::OFFSET + 1;
    public const CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT = self::OFFSET + 2;

}