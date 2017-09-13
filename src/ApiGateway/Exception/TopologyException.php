<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class TopologyException
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Exception
 */
class TopologyException extends PipesFrameworkException
{

    protected const OFFSET = 2400;

    public const CANNOT_DELETE_PUBLIC_TOPOLOGY = self::OFFSET + 1;
    public const TOPOLOGY_NODE_NAME_NOT_FOUND  = self::OFFSET + 2;
    public const TOPOLOGY_NODE_TYPE_NOT_FOUND  = self::OFFSET + 3;
    public const TOPOLOGY_NODE_TYPE_NOT_EXIST  = self::OFFSET + 4;

}