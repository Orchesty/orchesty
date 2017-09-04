<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Exception;

/**
 * Class TopologyException
 *
 * @package Hanaboso\PipesFramework\Commons\Exception
 */
class TopologyException extends PipesFrameworkException
{

    protected const OFFSET = 2200;

    public const INVALID_TOPOLOGY_TYPE = self::OFFSET + 1;

}