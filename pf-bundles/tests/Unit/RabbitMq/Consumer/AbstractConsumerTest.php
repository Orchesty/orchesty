<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.8.17
 * Time: 15:58
 */

namespace Tests\Unit\RabbitMq\Consumer;

use Exception;
use Hanaboso\PipesFramework\RabbitMq\Consumer\BaseSyncConsumerAbstract;
use Hanaboso\PipesFramework\RabbitMq\Serializers\JsonSerializer;
use Tests\KernelTestCaseAbstract;

/**
 * Class AbstractConsumerTest
 *
 * @package Tests\Unit\RabbitMq\Base
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

        $this->assertEquals('', $consumer->getExchange());
        $this->assertEquals('', $consumer->getRoutingKey());
        $this->assertEquals('', $consumer->getQueue());
        $this->assertEquals('', $consumer->getConsumerTag());
        $this->assertFalse($consumer->isNoLocal());
        $this->assertFalse($consumer->isNoAck());
        $this->assertFalse($consumer->isExclusive());
        $this->assertFalse($consumer->isNowait());
        $this->assertEquals([], $consumer->getArguments());
        $this->assertEquals(1, $consumer->getPrefetchCount());
        $this->assertEquals(0, $consumer->getPrefetchSize());
        $this->assertNull($consumer->getSerializer());
        $this->assertNull($consumer->getSetUpMethod());
        $this->assertNull($consumer->getTickMethod());
        $this->assertNull($consumer->getTickSeconds());
        $this->assertNull($consumer->getMaxMessages());
        $this->assertNull($consumer->getMaxSeconds());
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

        $this->assertEquals('foo', $consumer->getExchange());
        $this->assertEquals('*', $consumer->getRoutingKey());
        $this->assertEquals('queue_foo', $consumer->getQueue());
        $this->assertEquals('act_0123456879', $consumer->getConsumerTag());
        $this->assertTrue($consumer->isNoLocal());
        $this->assertFalse($consumer->isNoAck());
        $this->assertTrue($consumer->isExclusive());
        $this->assertTrue($consumer->isNowait());
        $this->assertEquals(['x-header-dead' => 500], $consumer->getArguments());
        $this->assertEquals(10, $consumer->getPrefetchCount());
        $this->assertEquals(500, $consumer->getPrefetchSize());
        $this->assertEquals('Hanaboso\PipesFramework\RabbitMq\Serializers\JsonSerializer', $consumer->getSerializer());
        $this->assertNull($consumer->getSetUpMethod());
        $this->assertEquals('tick_up', $consumer->getTickMethod());
        $this->assertEquals(5, $consumer->getTickSeconds());
        $this->assertEquals(200, $consumer->getMaxMessages());
        $this->assertEquals(10, $consumer->getMaxSeconds());
    }

}
