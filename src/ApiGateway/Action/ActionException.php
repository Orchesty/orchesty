<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Action;

use Exception;

/**
 * Class ActionException
 *
 * Codes are the HTTP status codes the controller answers with.
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Action
 */
final class ActionException extends Exception
{

    public const int INVALID_REQUEST = 400;
    public const int UNAUTHORIZED    = 401;
    public const int FORBIDDEN       = 403;
    public const int NOT_FOUND       = 404;

}
