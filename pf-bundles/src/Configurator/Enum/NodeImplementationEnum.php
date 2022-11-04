<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Enum;

use Hanaboso\Utils\Enum\EnumAbstract;

/**
 * Class NodeImplementationEnum
 *
 * @package Hanaboso\PipesFramework\Configurator\Enum
 */
final class NodeImplementationEnum extends EnumAbstract
{

    public const CONNECTOR = 'connector';
    public const CUSTOM    = 'custom';
    public const USER      = 'user';
    public const BATCH     = 'batch';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::CONNECTOR => self::CONNECTOR,
        self::CUSTOM    => self::CUSTOM,
        self::USER      => self::USER,
        self::BATCH     => self::BATCH,
    ];

}
