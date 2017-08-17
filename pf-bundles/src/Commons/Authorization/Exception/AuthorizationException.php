<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 10.8.17
 * Time: 11:04
 */

namespace Hanaboso\PipesFramework\Commons\Authorization\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class AuthorizationException
 */
final class AuthorizationException extends PipesFrameworkException
{

    protected const OFFSET = 200;

    public const TOKEN_NOT_FOUND  = self::OFFSET + 1;
    public const MISSING_ARGUMENT = self::OFFSET + 2;

}
