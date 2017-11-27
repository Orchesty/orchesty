<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstract;

/**
 * Class TypeEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
class TypeEnum extends EnumAbstract
{

    public const TEXT   = 'text';
    public const URL    = 'url';
    public const DATE   = 'date';
    public const BOOL   = 'bool';
    public const NUMBER = 'number';
    public const EMAIL  = 'email';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::TEXT   => 'text',
        self::URL    => 'url',
        self::DATE   => 'date',
        self::BOOL   => 'bool',
        self::NUMBER => 'number',
        self::EMAIL  => 'email',
    ];

}