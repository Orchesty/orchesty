<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstract;

/**
 * Class PropertyEnum
 *
 * @package Hanaboso\PipesFramework\Acl\Enum
 */
final class PropertyEnum extends EnumAbstract
{

    public const OWNER = 'owner';
    public const GROUP = 'group';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::OWNER => 'Owner',
        self::GROUP => 'Group',
    ];

}