<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class ApplicationInstallException
 *
 * @package Hanaboso\PipesPhpSdk\Application\Exception
 */
final class ApplicationInstallException extends PipesFrameworkExceptionAbstract
{

    public const int APP_ALREADY_INSTALLED      = self::OFFSET + 1;
    public const int APP_WAS_NOT_FOUND          = self::OFFSET + 2;
    public const int INVALID_FIELD_TYPE         = self::OFFSET + 3;
    public const int AUTHORIZATION_OAUTH2_ERROR = self::OFFSET + 4;
    public const int METHOD_NOT_FOUND           = self::OFFSET + 5;
    public const int INVALID_CUSTOM_ACTION_TYPE = self::OFFSET + 6;
    public const int MISSING_REQUIRED_PARAMETER = self::OFFSET + 7;

    protected const int OFFSET = 3_000;

}
