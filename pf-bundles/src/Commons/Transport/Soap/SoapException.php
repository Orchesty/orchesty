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

    public const UNKNOWN_EXCEPTION     = 900;
    public const UNKNOWN_SOAP_VERSION  = 901;
    public const INVALID_FUNCTION_CALL = 902;
    public const INVALID_WSDL          = 903;

}