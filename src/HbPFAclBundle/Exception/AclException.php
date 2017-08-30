<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFAclBundle\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class AclException
 *
 * @package Hanaboso\PipesFramework\HbPFAclBundle\Exception
 */
final class AclException extends PipesFrameworkException
{

    protected const OFFSET = 1900;

    public const ACL_NOT_EXIST      = self::OFFSET + 1;
    public const ACL_NOT_ARRAY      = self::OFFSET + 2;
    public const RESOURCE_NOT_EXIST = self::OFFSET + 3;

}