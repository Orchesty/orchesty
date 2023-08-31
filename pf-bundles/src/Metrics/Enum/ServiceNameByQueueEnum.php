<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Enum;

use Hanaboso\Utils\Enum\EnumAbstract;

/**
 * Class ServiceNameByQueueEnum
 *
 * @package Hanaboso\PipesFramework\Metrics\Enum
 */
final class ServiceNameByQueueEnum extends EnumAbstract
{

    public const REPEATER      = 'pipes.repeater';
    public const LIMITER       = 'pipes.limiter';
    public const MULTI_COUNTER = 'pipes.multi-counter';
    public const BRIDGE        = 'bridge';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::REPEATER      => 'Repeater',
        self::LIMITER       => 'Limiter',
        self::MULTI_COUNTER => 'Multi counter',
    ];

    /**
     * @param string $queue
     *
     * @return array|string[]
     */
    public static function getNameAndNodeId(string $queue): array
    {
        $matches = [];
        if (preg_match('/node\.(\d\w+)\.\d+/', $queue, $matches)) {
            return [
                'name'   => self::BRIDGE,
                'nodeId' => $matches[1],
            ];
        }

        return ['name' => self::$choices[$queue] ?? 'Unknown service'];
    }

}
