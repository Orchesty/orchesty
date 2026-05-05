<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class PlatformServiceException
 *
 * Non-final on purpose: `QuotaExceededException` extends it so that any code
 * `catch (PlatformServiceException $e)` keeps catching the specialized
 * trace-quota subclass without changes.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception
 */
class PlatformServiceException extends PipesFrameworkExceptionAbstract
{

    public const int BINDING_NOT_FOUND = self::OFFSET + 1;
    public const int CALL_FAILED       = self::OFFSET + 2;
    public const int QUOTA_EXCEEDED    = self::OFFSET + 3;
    public const int RELAY_FAILED      = self::OFFSET + 4;

    protected const int OFFSET = 3_200;

}
