<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class CustomNodeException
 *
 * @package Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception
 */
final class CustomNodeException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 1800;

    public const CUSTOM_NODE_SERVICE_NOT_FOUND = self::OFFSET + 1;

}
