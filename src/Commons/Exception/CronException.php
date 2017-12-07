<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Exception;

/**
 * Class CronException
 *
 * @package Hanaboso\PipesFramework\Commons\Exception
 */
final class CronException extends PipesFrameworkException
{

    protected const OFFSET = 2700;

    public const CRON_EXCEPTION = self::OFFSET + 1;

}