<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstraction;

/**
 * Class ResourceEnum
 *
 * @package Hanaboso\PipesFramework\Acl\Enum
 */
final class ResourceEnum extends EnumAbstraction
{

    public const GROUP = 'group';
    public const USER  = 'user';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::GROUP => 'Group document',
        self::USER  => 'User document',
    ];

}