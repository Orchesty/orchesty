<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 30.8.17
 * Time: 20:30
 */

namespace Tests\Unit\RabbitMq\Repeater;

use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Impl\Repeater\Repeater;
use Hanaboso\PipesFramework\RabbitMq\Impl\Repeater\RepeaterCallback;
use Hanaboso\PipesFramework\RabbitMq\Impl\Repeater\RepeaterProducer;
use PHPUnit\Framework\TestCase;

/**
 * Class RepeaterCallbackTest
 *
 * @package Tests\Unit\RabbitMq\Repeater
 */
class RepeaterCallbackTest extends TestCase
{

    /**
     * @var Message
     */
    protected $message;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->message = new Message(
            'tag',
            'tag',
            FALSE,
            '',
            '',
            [],
            'content'
        );
    }

    /**
     * @covers       RepeaterCallback::handle()
     *
     * @return void
     */
    public function testHandleNoProducer(): void
    {
        /** @var RepeaterCallback $callback */
        $callback = new RepeaterCallback();
        $result   = $callback->handle([], $this->message);
        $this->assertInstanceOf(CallbackStatus::class, $result);
        $this->assertEquals(1, $result->getStatus());
    }

    /**
     * @covers RepeaterCallback::handle()
     * @return void
     */
    public function testHandleBadMessage(): void
    {
        $producer = $this->getMockBuilder(RepeaterProducer::class)->disableOriginalConstructor()->getMock();
        $producer->expects($this->never())->method('publish')->willReturn(TRUE);
        $callback = new RepeaterCallback($producer);
        $result   = $callback->handle([], $this->message);
        $this->assertInstanceOf(CallbackStatus::class, $result);
        $this->assertEquals(1, $result->getStatus());
    }

    /**
     * @covers RepeaterCallback::handle()
     * @return void
     */
    public function testHandle(): void
    {
        $this->message->headers = [
            Repeater::DESTINATION_EXCHANGE    => 'test',
            Repeater::DESTINATION_ROUTING_KEY => 'test',
        ];

        $producer = $this->getMockBuilder(RepeaterProducer::class)->disableOriginalConstructor()->getMock();
        $producer->expects($this->once())->method('publish')->willReturn(TRUE);

        $callback = new RepeaterCallback($producer);
        $result   = $callback->handle([], $this->message);
        $this->assertInstanceOf(CallbackStatus::class, $result);
        $this->assertEquals(1, $result->getStatus());
    }

}
