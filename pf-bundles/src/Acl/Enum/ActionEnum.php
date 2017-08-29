<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstraction;

/**
 * Class ActionEnum
 *
 * @package Hanaboso\PipesFramework\Acl\Enum
 */
final class ActionEnum extends EnumAbstraction
{

    /**
     * @var string[]
     */
    protected static $choices = [
        self::READ   => 'Read',
        self::WRITE  => 'Write',
        self::DELETE => 'Delete',
    ];

    public const READ   = 'read';
    public const WRITE  = 'write';
    public const DELETE = 'delete';

}