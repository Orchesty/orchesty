<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class TopologyException
 *
 * @package Hanaboso\PipesFramework\Configurator\Exception
 */
final class TopologyException extends PipesFrameworkExceptionAbstract
{

    public const INVALID_TOPOLOGY_TYPE            = self::OFFSET + 1;
    public const TOPOLOGY_NOT_FOUND               = self::OFFSET + 2;
    public const CANNOT_DELETE_PUBLIC_TOPOLOGY    = self::OFFSET + 3;
    public const TOPOLOGY_NODE_NAME_NOT_FOUND     = self::OFFSET + 4;
    public const TOPOLOGY_NODE_TYPE_NOT_FOUND     = self::OFFSET + 5;
    public const TOPOLOGY_NODE_TYPE_NOT_EXIST     = self::OFFSET + 6;
    public const TOPOLOGY_HAS_NO_NODES            = self::OFFSET + 7;
    public const TOPOLOGY_NAME_ALREADY_EXISTS     = self::OFFSET + 8;
    public const TOPOLOGY_CANNOT_CHANGE_NAME      = self::OFFSET + 9;
    public const TOPOLOGY_NODE_CRON_NOT_VALID     = self::OFFSET + 10;
    public const SCHEMA_START_NODE_MISSING        = self::OFFSET + 11;
    public const SCHEMA_INFINITE_LOOP             = self::OFFSET + 12;
    public const TOPOLOGY_NODE_CRON_NOT_AVAILABLE = self::OFFSET + 13;
    public const SDK_HEADERS_NOT_FOUND            = self::OFFSET + 14;
    public const UNSUPPORTED_SCHEMA               = self::OFFSET + 15;

    protected const OFFSET = 2_400;

}
