<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\Security;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class SecurityManagerException
 *
 * @package Hanaboso\PipesFramework\User\Model\Security
 */
final class SecurityManagerException extends PipesFrameworkException
{

    protected const OFFSET = 1500;

    public const USER_OR_PASSWORD_NOT_VALID = self::OFFSET + 1;
    public const USER_ENCODER_NOT_FOUND     = self::OFFSET + 2;

}