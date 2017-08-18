<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class SoapException
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap
 */
final class SoapException extends PipesFrameworkException
{

    protected const OFFSET = 900;

    public const UNKNOWN_EXCEPTION     = self::OFFSET + 0;
    public const UNKNOWN_SOAP_VERSION  = self::OFFSET + 1;
    public const INVALID_FUNCTION_CALL = self::OFFSET + 2;
    public const INVALID_WSDL          = self::OFFSET + 3;

}