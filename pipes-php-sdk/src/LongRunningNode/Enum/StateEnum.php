<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class StateEnum
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Enum
 */
final class StateEnum extends EnumAbstract
{

    public const PENDING     = 'pending';
    public const IN_PROGRESS = 'in_progress';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::PENDING     => self::PENDING,
        self::IN_PROGRESS => self::IN_PROGRESS,
    ];

}
