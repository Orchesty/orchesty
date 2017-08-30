<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class AclException
 *
 * @package Hanaboso\PipesFramework\Acl\Exception
 */
class AclException extends PipesFrameworkException
{

    protected const OFFSET = 1000;

    public const MISSING_DATA = self::OFFSET + 1;
    public const ZERO_MASK    = self::OFFSET + 2;

}