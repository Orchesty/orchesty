<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Enum;

use Hanaboso\Utils\Enum\EnumAbstract;

/**
 * Class StateEnum
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Enum
 */
final class StateEnum extends EnumAbstract
{

    public const NEW      = 'new';
    public const ACCEPTED = 'accepted';
    public const CANCELED = 'canceled';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::NEW      => self::NEW,
        self::ACCEPTED => self::ACCEPTED,
        self::CANCELED => self::CANCELED,
    ];

}
