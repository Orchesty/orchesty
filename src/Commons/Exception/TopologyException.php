<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Exception;

/**
 * Class TopologyException
 *
 * @package Hanaboso\PipesFramework\Commons\Exception
 */
final class TopologyException extends PipesFrameworkException
{

    protected const OFFSET = 2200;

    public const INVALID_TOPOLOGY_TYPE = self::OFFSET + 1;
    public const TOPOLOGY_NOT_FOUND    = self::OFFSET + 2;

}