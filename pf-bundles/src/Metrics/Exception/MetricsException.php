<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class MetricsException
 *
 * @package Hanaboso\PipesFramework\Metrics\Exception
 */
final class MetricsException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 2_800;

    public const DB_NOT_EXIST       = self::OFFSET + 1;
    public const NODE_NOT_FOUND     = self::OFFSET + 2;
    public const TOPOLOGY_NOT_FOUND = self::OFFSET + 3;
    public const QUERY_ERROR        = self::OFFSET + 4;

}
