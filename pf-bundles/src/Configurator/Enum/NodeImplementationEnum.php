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

    public const PHP = 'php';

    public const CONNECTOR = 'connector';
    public const CUSTOM    = 'custom';
    public const USER      = 'user';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::PHP => 'Pipes Framework Implementation',
    ];

}
