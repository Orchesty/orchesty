<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Acl\Enum;

use Hanaboso\AclBundle\Enum\ActionEnum as BaseActionEnum;

/**
 * Class ActionEnum
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Acl\Enum
 */
class ActionEnum extends BaseActionEnum
{

    public const string RUN = 'run';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::DELETE => self::DELETE,
        self::READ   => self::READ,
        self::RUN    => self::RUN,
        self::WRITE  => self::WRITE,
    ];

}
