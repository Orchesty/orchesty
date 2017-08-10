<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 10.8.17
 * Time: 11:04
 */

namespace Hanaboso\PipesFramework\Commons\Authorization\Exception;

use Exception;

/**
 * Class AuthorizationException
 */
final class AuthorizationException extends Exception
{

    public const TOKEN_NOT_FOUND = 1;

}
