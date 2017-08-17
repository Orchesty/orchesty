<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Cryptography;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class CryptException
 *
 * @package Hanaboso\PipesFramework\Commons\Crypt
 */
class CryptException extends PipesFrameworkException
{

    protected const OFFSET = 1400;

    public const UNKNOWN_PREFIX = self::OFFSET + 1;

}