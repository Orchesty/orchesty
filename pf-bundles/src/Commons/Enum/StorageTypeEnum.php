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
final class StorageTypeEnum extends EnumAbstract
{

    public const PERSISTENT = 'persistent';
    public const TEMPORARY  = 'temporary';
    public const PUBLIC     = 'public';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::PERSISTENT => 'Persistent',
        self::TEMPORARY  => 'Temporary',
        self::PUBLIC     => 'Public',
    ];

}