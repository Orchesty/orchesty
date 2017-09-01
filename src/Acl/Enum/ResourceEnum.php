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

    public const GROUP       = 'group';
    public const USER        = 'user';
    public const TMP_USER    = 'tmp_user';
    public const TOKEN       = 'token';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::GROUP       => 'Group entity',
        self::USER        => 'User entity',
        self::TMP_USER    => 'TmpUser entity',
        self::TOKEN       => 'Token entity',
    ];

}