<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Enum;

/**
 * Class ServiceNameByQueueEnum
 *
 * @package Hanaboso\PipesFramework\Metrics\Enum
 */
enum ServiceNameByQueueEnum: string
{

    case REPEATER      = 'pipes.repeater';
    case LIMITER       = 'pipes.limiter';
    case MULTI_COUNTER = 'pipes.multi-counter';
    case BRIDGE        = 'bridge';

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
                'name'   => self::BRIDGE->value,
                'nodeId' => $matches[1],
            ];
        }

        $queueValue = match (self::tryFrom($queue)) {
            self::REPEATER => 'Repeater',
            self::LIMITER => 'Limiter',
            self::MULTI_COUNTER => 'Multi counter',
            default => NULL,
        };

        return ['name' => $queueValue ?? 'Unknown service'];
    }

}
