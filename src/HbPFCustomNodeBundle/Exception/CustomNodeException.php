<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;

/**
 * Class CustomNodeException
 *
 * @package Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception
 */
final class CustomNodeException extends PipesFrameworkException
{

    protected const OFFSET = 1800;

    public const CUSTOM_NODE_SERVICE_NOT_FOUND = self::OFFSET + 1;

}
