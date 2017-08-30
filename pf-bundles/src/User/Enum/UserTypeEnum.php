<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstraction;

/**
 * Class UserTypeEnum
 *
 * @package Hanaboso\PipesFramework\User\Enum
 */
final class UserTypeEnum extends EnumAbstraction
{

    public const USER     = 'user';
    public const TMP_USER = 'tmpUser';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::USER     => 'User',
        self::TMP_USER => 'Unactivated user',
    ];

}