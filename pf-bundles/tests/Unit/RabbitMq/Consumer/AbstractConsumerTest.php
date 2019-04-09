<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq\Consumer;

use Exception;
use Hanaboso\PipesFramework\RabbitMq\Consumer\BaseSyncConsumerAbstract;
use Hanaboso\PipesFramework\RabbitMq\Serializers\JsonSerializer;
use Tests\KernelTestCaseAbstract;

/**
 * Class AbstractConsumerTest
 *
 * @package Tests\Unit\RabbitMq\Consumer
 */
final class AbstractConsumerTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     * @throws Exception
     */
    public function testEmptyConsumer(): void
    {
        /** @var BaseSyncConsumerAbstract $consumer */
        $consumer = $this->getMockForAbstractClass(BaseSyncConsumerAbstract::class);

        self::assertEquals('', $consumer->getExchange());
        self::assertEquals('', $consumer->getRoutingKey());
        self::assertEquals('', $consumer->getQueue());
        self::assertEquals('', $consumer->getConsumerTag());
        self::assertFalse($consumer->isNoLocal());
        self::assertFalse($consumer->isNoAck());
        self::assertFalse($consumer->isExclusive());
        self::assertFalse($consumer->isNowait());
        self::assertEquals([], $consumer->getArguments());
        self::assertEquals(1, $consumer->getPrefetchCount());
        self::assertEquals(0, $consumer->getPrefetchSize());
        self::assertNull($consumer->getSerializer());
        self::assertNull($consumer->getSetUpMethod());
        self::assertNull($consumer->getTickMethod());
        self::assertNull($consumer->getTickSeconds());
        self::assertNull($consumer->getMaxMessages());
        self::assertNull($consumer->getMaxSeconds());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testFilledConsumer(): void
    {
        /** @var BaseSyncConsumerAbstract $consumer */
        $consumer = $this->getMockForAbstractClass(BaseSyncConsumerAbstract::class, [
            'foo',
            '*',
            'queue_foo',
            'act_0123456879',
            TRUE,
            FALSE,
            TRUE,
            TRUE,
            ['x-header-dead' => 500],
            10,
            500,
            JsonSerializer::class,
            NULL,
            'tick_up',
            5,
            200,
            10,
        ]);

        self::assertEquals('foo', $consumer->getExchange());
        self::assertEquals('*', $consumer->getRoutingKey());
        self::assertEquals('queue_foo', $consumer->getQueue());
        self::assertEquals('act_0123456879', $consumer->getConsumerTag());
        self::assertTrue($consumer->isNoLocal());
        self::assertFalse($consumer->isNoAck());
        self::assertTrue($consumer->isExclusive());
        self::assertTrue($consumer->isNowait());
        self::assertEquals(['x-header-dead' => 500], $consumer->getArguments());
        self::assertEquals(10, $consumer->getPrefetchCount());
        self::assertEquals(500, $consumer->getPrefetchSize());
        self::assertEquals('Hanaboso\PipesFramework\RabbitMq\Serializers\JsonSerializer', $consumer->getSerializer());
        self::assertNull($consumer->getSetUpMethod());
        self::assertEquals('tick_up', $consumer->getTickMethod());
        self::assertEquals(5, $consumer->getTickSeconds());
        self::assertEquals(200, $consumer->getMaxMessages());
        self::assertEquals(10, $consumer->getMaxSeconds());
    }

}
