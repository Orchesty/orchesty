<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstract;

/**
 * Class ActionEnum
 *
 * @package Hanaboso\PipesFramework\Acl\Enum
 */
final class ActionEnum extends EnumAbstract
{

    public const READ   = 'read';
    public const WRITE  = 'write';
    public const DELETE = 'delete';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::READ   => 'Read',
        self::WRITE  => 'Write',
        self::DELETE => 'Delete',
    ];

}