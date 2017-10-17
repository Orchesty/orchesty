<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 6.10.17
 * Time: 9:19
 */

namespace CcApi\Connector\Exception;

use Exception;

/**
 * Class ConnectorException
 *
 * @package CcApi\Connector\Exception
 */
class ConnectorException extends Exception
{

    public const REQUEST_ERROR = 1;
    public const PARSER_ERROR  = 2;

}