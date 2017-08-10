<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Curl;

use Exception;

/**
 * Class CurlException
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Curl
 */
final class CurlException extends Exception
{

    public const INVALID_METHOD = 1;
    public const BODY_ON_GET    = 2;
    public const REQUEST_FAILED = 3;

}