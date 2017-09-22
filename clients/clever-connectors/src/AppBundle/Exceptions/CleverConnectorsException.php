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

}