<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;

/**
 * Class AclException
 *
 * @package Hanaboso\PipesFramework\Acl\Exception
 */
final class AclException extends PipesFrameworkException
{

    protected const OFFSET = 2100;

    public const MISSING_DATA          = self::OFFSET + 1;
    public const ZERO_MASK             = self::OFFSET + 2;
    public const MISSING_DEFAULT_RULES = self::OFFSET + 3;
    public const PERMISSION            = self::OFFSET + 4;
    public const INVALID_RESOURCE      = self::OFFSET + 5;
    public const INVALID_ACTION        = self::OFFSET + 6;

}