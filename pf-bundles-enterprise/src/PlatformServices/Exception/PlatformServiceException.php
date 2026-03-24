<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class PlatformServiceException
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception
 */
final class PlatformServiceException extends PipesFrameworkExceptionAbstract
{

    public const int BINDING_NOT_FOUND = self::OFFSET + 1;
    public const int CALL_FAILED       = self::OFFSET + 2;

    protected const int OFFSET = 3_200;

}
