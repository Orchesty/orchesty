<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstraction;

/**
 * Class UserTypeEnum
 *
 * @package Hanaboso\PipesFramework\User\Enum
 */
class UserTypeEnum extends EnumAbstraction
{

    /**
     * @var string[]
     */
    protected static $choices = [
        self::USER     => 'User',
        self::TMP_USER => 'Unactivated user',
    ];

    public const USER     = 'user';
    public const TMP_USER = 'tmpUser';

}