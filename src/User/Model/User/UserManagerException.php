<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\User;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class UserManagerException
 *
 * @package Hanaboso\PipesFramework\User\Model\User
 */
final class UserManagerException extends PipesFrameworkException
{

    protected const OFFSET = 1300;

    public const USER_EMAIL_NOT_EXISTS     = self::OFFSET + 1;
    public const USER_EMAIL_ALREADY_EXISTS = self::OFFSET + 2;

}