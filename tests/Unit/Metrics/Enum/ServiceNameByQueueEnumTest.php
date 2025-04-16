<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Metrics\Enum;

use Exception;
use Hanaboso\PipesFramework\Metrics\Enum\ServiceNameByQueueEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class ServiceNameByQueueEnumTest
 *
 * @package PipesFrameworkTests\Unit\Metrics\Enum
 */
#[CoversClass(ServiceNameByQueueEnum::class)]
final class ServiceNameByQueueEnumTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testBillingEvent(): void
    {
        $repeater = ServiceNameByQueueEnum::getNameAndNodeId('pipes.repeater');
        self::assertSame('Repeater', $repeater['name']);
        $limiter = ServiceNameByQueueEnum::getNameAndNodeId('pipes.limiter');
        self::assertSame('Limiter', $limiter['name']);
        $multiCounter = ServiceNameByQueueEnum::getNameAndNodeId('pipes.multi-counter');
        self::assertSame('Multi counter', $multiCounter['name']);
        $neco = ServiceNameByQueueEnum::getNameAndNodeId('neco');
        self::assertSame('Unknown service', $neco['name']);
        $bridge = ServiceNameByQueueEnum::getNameAndNodeId('node.123abc.123');
        self::assertSame('bridge', $bridge['name']);
        self::assertSame('123abc', $bridge['nodeId']);
    }

}
