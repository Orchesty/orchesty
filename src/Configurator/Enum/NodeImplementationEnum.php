<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class NodeImplementationEnum
 *
 * @package Hanaboso\PipesFramework\Configurator\Enum
 */
final class NodeImplementationEnum extends EnumAbstract
{

    public const PHP = 'php';

    public const CONNECTOR = 'connector';
    public const CUSTOM    = 'custom';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::PHP => 'Pipes Framework Implementation',
    ];

}
