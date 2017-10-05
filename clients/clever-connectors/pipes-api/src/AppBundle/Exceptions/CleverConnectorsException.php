<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Exceptions;

/**
 * Class CleverConnectorsException
 *
 * @package CleverConnectors\AppBundle\Exceptions
 */
class CleverConnectorsException extends Exception
{

    public const WEBHOOK_NOT_FOUND     = 1;
    public const USER_TOKEN_NOT_EXISTS = 2;
    public const MISSING_DATA          = 3;
    public const INVALID_FIELD_TYPE    = 4;
    public const SYSTEM_NOT_INSTALLED  = 5;

}