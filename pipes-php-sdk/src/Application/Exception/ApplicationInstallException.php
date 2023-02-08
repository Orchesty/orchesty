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

    public const APP_ALREADY_INSTALLED      = self::OFFSET + 1;
    public const APP_WAS_NOT_FOUND          = self::OFFSET + 2;
    public const INVALID_FIELD_TYPE         = self::OFFSET + 3;
    public const AUTHORIZATION_OAUTH2_ERROR = self::OFFSET + 4;
    public const METHOD_NOT_FOUND           = self::OFFSET + 5;
    public const INVALID_CUSTOM_ACTION_TYPE = self::OFFSET + 6;
    public const MISSING_REQUIRED_PARAMETER = self::OFFSET + 7;

    protected const OFFSET = 3_000;

}
