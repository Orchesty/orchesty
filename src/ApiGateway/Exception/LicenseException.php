<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class LicenseException
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Exception
 */
final class LicenseException extends PipesFrameworkExceptionAbstract
{

    public const LICENSE_NOT_VALID_OR_APPS_EXCEED  = self::OFFSET + 1;
    public const LICENSE_NOT_VALID_OR_USERS_EXCEED = self::OFFSET + 2;

    protected const OFFSET = 3_100;

}
