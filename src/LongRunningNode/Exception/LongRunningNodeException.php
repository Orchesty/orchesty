<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;

/**
 * Class LongRunningNodeException
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Exception
 */
final class LongRunningNodeException extends PipesFrameworkException
{

    private const OFFSET = 2700;

    public const LONG_RUNNING_SERVICE_NOT_FOUND = self::OFFSET + 1;

}