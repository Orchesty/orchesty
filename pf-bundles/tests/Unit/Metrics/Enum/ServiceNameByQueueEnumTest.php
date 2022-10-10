<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Metrics\Enum;

use Exception;
use Hanaboso\PipesFramework\Metrics\Enum\ServiceNameByQueueEnum;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class ServiceNameByQueueEnumTest
 *
 * @package PipesFrameworkTests\Unit\Metrics\Enum
 */
final class ServiceNameByQueueEnumTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Enum\ServiceNameByQueueEnum
     * @covers \Hanaboso\PipesFramework\Metrics\Enum\ServiceNameByQueueEnum::getNameAndNodeId
     * @covers \Hanaboso\PipesFramework\Metrics\Enum\ServiceNameByQueueEnum::getChoices
     *
     * @throws Exception
     */
    public function testBillingEvent(): void
    {
        $repeater = ServiceNameByQueueEnum::getNameAndNodeId('pipes.repeater');
        self::assertEquals('Repeater', $repeater['name']);
        $limiter = ServiceNameByQueueEnum::getNameAndNodeId('pipes.limiter');
        self::assertEquals('Limiter', $limiter['name']);
        $multiCounter = ServiceNameByQueueEnum::getNameAndNodeId('pipes.multi-counter');
        self::assertEquals('Multi counter', $multiCounter['name']);
        $neco = ServiceNameByQueueEnum::getNameAndNodeId('neco');
        self::assertEquals('Unknown service', $neco['name']);
        $bridge = ServiceNameByQueueEnum::getNameAndNodeId('node.123abc.123');
        self::assertEquals('bridge', $bridge['name']);
        self::assertEquals('123abc', $bridge['nodeId']);

        self::assertEquals(
            [
                'pipes.repeater' => 'Repeater',
                'pipes.limiter' => 'Limiter',
                'pipes.multi-counter' => 'Multi counter',
            ],
            ServiceNameByQueueEnum::getChoices(),
        );
    }

}
