<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Enum;

/**
 * Class HandlerEnum
 *
 * @package Hanaboso\PipesFramework\Commons\Enum
 */
class HandlerEnum extends EnumAbstraction
{

    public const ACTION = 'action';
    public const EVENT  = 'event';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::ACTION => 'action',
        self::EVENT  => 'event',
    ];

}