<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Exception;

/**
 * Class NodeException
 *
 * @package Hanaboso\PipesFramework\Commons\Exception
 */
final class NodeException extends PipesFrameworkException
{

    protected const OFFSET = 2300;

    public const INVALID_TYPE    = self::OFFSET + 1;
    public const INVALID_HANDLER = self::OFFSET + 2;

}