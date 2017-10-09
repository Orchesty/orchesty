<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Exceptions;

use CleverConnectors\AppBundle\Exceptions\Exception;

/**
 * Class SystemException
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Exceptions
 */
final class SystemException extends Exception
{

    public const SYSTEM_NOT_FOUND          = 1;
    public const SYSTEM_METHOD_NOT_FOUND   = 2;
    public const SYSTEM_PROPERTY_NOT_FOUND = 3;
    public const SYSTEM_OR_USER_NOT_FOUND  = 4;
    public const SYSTEM_IS_UNAUTHORIZED    = 5;
    public const MISSING_RESPONSE_DATA     = 6;
    public const MISSING_DATA              = 7;

}