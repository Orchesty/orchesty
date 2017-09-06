<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstract;

/**
 * Class ResourceEnum
 *
 * @package Hanaboso\PipesFramework\Acl\Enum
 */
final class ResourceEnum extends EnumAbstract
{

    public const GROUP    = 'group';
    public const USER     = 'user';
    public const TMP_USER = 'tmp_user';
    public const TOKEN    = 'token';
    public const TOPOLOGY = 'topology';
    public const FILE     = 'file';
    public const NODE     = 'node';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::GROUP    => 'Group entity',
        self::USER     => 'User entity',
        self::TMP_USER => 'TmpUser entity',
        self::TOKEN    => 'Token entity',
        self::TOPOLOGY => 'Topology entity',
        self::FILE     => 'File',
        self::NODE     => 'Node entity',
    ];

}