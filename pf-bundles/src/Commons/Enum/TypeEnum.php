<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Enum;

/**
 * Class TypeEnum
 *
 * @package Hanaboso\PipesFramework\Commons\Enum
 */
class TypeEnum extends EnumAbstract
{

    public const CONNECTOR = 'connector';
    public const MAPPER    = 'mapper';
    public const PARSER    = 'parser';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::CONNECTOR => 'connector',
        self::MAPPER    => 'mapper',
        self::PARSER    => 'parser',
    ];

}