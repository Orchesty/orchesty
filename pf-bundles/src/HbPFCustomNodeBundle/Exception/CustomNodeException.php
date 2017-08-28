<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:44 PM
 */

namespace Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

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