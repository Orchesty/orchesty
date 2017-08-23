<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/21/17
 * Time: 1:07 PM
 */

namespace Hanaboso\PipesFramework\Commons\Enum;

/**
 * Class StorageTypeEnum
 *
 * @package Hanaboso\PipesFramework\Commons\Enum
 */
final class StorageTypeEnum
{

    public const PERSISTENT = 'persistent';
    public const TEMPORARY  = 'temporary';
    public const PUBLIC     = 'public';

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, [self::PERSISTENT, self::PUBLIC, self::TEMPORARY]);
    }

}