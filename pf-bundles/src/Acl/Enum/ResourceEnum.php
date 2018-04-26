<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class ResourceEnum
 *
 * @package Hanaboso\PipesFramework\Acl\Enum
 */
class ResourceEnum extends EnumAbstract
{

    //TODO temporary till AclBundle is imported
    public const GROUP    = 'group';
    public const USER     = 'user';
    public const TMP_USER = 'tmp_user';
    public const TOKEN    = 'token';
    public const FILE     = 'file';
    public const RULE     = 'rule';
    public const NODE     = 'node';
    public const TOPOLOGY = 'node';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::GROUP    => 'Group entity',
        self::USER     => 'User entity',
        self::TMP_USER => 'TmpUser entity',
        self::TOKEN    => 'Token entity',
        self::FILE     => 'File',
        self::RULE     => 'Rule',
        self::NODE     => 'Node',
        self::TOPOLOGY => 'Topology',
    ];

}