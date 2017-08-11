<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use Exception;

/**
 * Class SoapException
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap
 */
final class SoapException extends Exception
{

    public const UNKNOWN_SOAP_VERSION = 900;

}