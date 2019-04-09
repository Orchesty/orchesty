<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq\Repeater;

use Bunny\Message;
use Exception;
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
final class RepeaterCallbackTest extends TestCase
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
     * @throws Exception
     */
    public function testHandleNoProducer(): void
    {
        /** @var RepeaterCallback $callback */
        $callback = new RepeaterCallback();
        $result   = $callback->handle([], $this->message);
        self::assertInstanceOf(CallbackStatus::class, $result);
        self::assertEquals(1, $result->getStatus());
    }

    /**
     * @covers RepeaterCallback::handle()
     * @return void
     * @throws Exception
     */
    public function testHandleBadMessage(): void
    {
        $producer = self::getMockBuilder(RepeaterProducer::class)->disableOriginalConstructor()->getMock();
        $producer->expects($this->never())->method('publish')->willReturn(TRUE);
        $callback = new RepeaterCallback($producer);
        $result   = $callback->handle([], $this->message);
        self::assertInstanceOf(CallbackStatus::class, $result);
        self::assertEquals(1, $result->getStatus());
    }

    /**
     * @covers RepeaterCallback::handle()
     * @return void
     * @throws Exception
     */
    public function testHandle(): void
    {
        $this->message->headers = [
            Repeater::DESTINATION_EXCHANGE    => 'test',
            Repeater::DESTINATION_ROUTING_KEY => 'test',
        ];

        $producer = self::getMockBuilder(RepeaterProducer::class)->disableOriginalConstructor()->getMock();
        $producer->expects($this->once())->method('publish')->willReturn(TRUE);

        $callback = new RepeaterCallback($producer);
        $result   = $callback->handle([], $this->message);
        self::assertInstanceOf(CallbackStatus::class, $result);
        self::assertEquals(1, $result->getStatus());
    }

}
