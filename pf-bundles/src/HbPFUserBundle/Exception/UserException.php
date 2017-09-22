<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class UserException
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Exception
 */
class UserException extends PipesFrameworkException
{

    protected const OFFSET = 1900;

    public const RESOURCE_NOT_EXIST = self::OFFSET + 1;
    public const RULESET_NOT_EXIST  = self::OFFSET + 2;

}