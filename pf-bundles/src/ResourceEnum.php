<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework;

use Hanaboso\AclBundle\Enum\ResourceEnum as AclResourceEnum;

/**
 * Class ResourceEnum
 *
 * @package Hanaboso\PipesFramework
 */
final class ResourceEnum extends AclResourceEnum
{

    public const NODE     = 'node';
    public const TOPOLOGY = 'topology';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::FILE     => 'File',
        self::GROUP    => 'Group entity',
        self::NODE     => 'Node',
        self::RULE     => 'Rule',
        self::TMP_USER => 'TmpUser entity',
        self::TOKEN    => 'Token entity',
        self::TOPOLOGY => 'Topology',
        self::USER     => 'User entity',
    ];

}
