<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class NodeException
 *
 * @package Hanaboso\PipesFramework\Configurator\Exception
 */
final class NodeException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 2300;

    public const INVALID_TYPE                        = self::OFFSET + 1;
    public const INVALID_HANDLER                     = self::OFFSET + 2;
    public const NODE_NOT_FOUND                      = self::OFFSET + 3;
    public const DISALLOWED_ACTION_ON_NON_EVENT_NODE = self::OFFSET + 4;

}