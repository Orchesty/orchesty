<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework;

use Hanaboso\AclBundle\Enum\ResourceEnum as AclResourceEnum;

/**
 * Class ResourceEnum
 *
 * @package Hanaboso\PipesFramework\Acl\Enum
 */
class ResourceEnum extends AclResourceEnum
{

    public const NODE     = 'node';
    public const TOPOLOGY = 'topology';

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