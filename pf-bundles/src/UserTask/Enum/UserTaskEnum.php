<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Enum;

use Hanaboso\Utils\Enum\EnumAbstract;

/**
 * Class UserTaskEnum
 *
 * @package Hanaboso\PipesFramework\UserTask\Enum
 */
final class UserTaskEnum extends EnumAbstract
{

    public const THRASH    = 'thrash';
    public const USER_TASK = 'userTask';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::THRASH    => self::THRASH,
        self::USER_TASK => self::USER_TASK,
    ];

}
