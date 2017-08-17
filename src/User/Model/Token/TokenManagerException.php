<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\Token;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class TokenManagerException
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Manager
 */
final class TokenManagerException extends PipesFrameworkException
{

    protected const OFFSET = 1100;

    public const TOKEN_NOT_VALID = self::OFFSET + 1;

}