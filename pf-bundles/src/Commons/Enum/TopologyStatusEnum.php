<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Enum;

/**
 * Class TopologyStatusEnum
 *
 * @package Hanaboso\PipesFramework\Commons\Enum
 */
class TopologyStatusEnum extends EnumAbstraction
{

    public const DRAFT  = 'draft';
    public const PUBLIC = 'public';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::DRAFT  => 'draft',
        self::PUBLIC => 'public',
    ];

}