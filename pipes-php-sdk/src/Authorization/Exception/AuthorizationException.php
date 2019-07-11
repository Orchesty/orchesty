<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class AuthorizationException
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Exception
 */
final class AuthorizationException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 200;

    public const AUTHORIZATION_SERVICE_NOT_FOUND       = self::OFFSET + 1;
    public const AUTHORIZATION_OAUTH1_ERROR            = self::OFFSET + 2;
    public const AUTHORIZATION_SETTINGS_NOT_FOUND      = self::OFFSET + 3;
    public const AUTHORIZATION_RESPONSE_ARRAY_EXPECTED = self::OFFSET + 4;
    public const AUTHORIZATION_OAUTH2_ERROR            = self::OFFSET + 5;

}