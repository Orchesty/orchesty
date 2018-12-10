<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 31.8.17
 * Time: 22:01
 */

namespace Tests\Unit\RabbitMq\Consumer;

use Bunny\Channel;
use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Consumer\SyncCallbackAbstract;
use Hanaboso\PipesFramework\RabbitMq\Exception\RabbitMqException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Repeater\Repeater;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class BaseCallbackAbstractTest
 *
 * @package Tests\Unit\RabbitMq\Base
 */
final class SyncCallbackAbstractTest extends TestCase
{

    /**
     * @dataProvider handleMessage
     * @covers       SyncCallbackAbstract::handleMessage()
     *
     * @param SyncCallbackAbstract $baseCallback
     * @param string|null          $exception
     * @param int|null             $callbackStatus
     *
     * @return void
     * @throws RabbitMqException
     */
    public function testHandleMessage(
        SyncCallbackAbstract $baseCallback,
        ?string $exception,
        ?int $callbackStatus
    ): void
    {
        if ($exception) {
            $this->expectException($exception);
        }
        $message = new Message('', '', FALSE, 'test', 'key', [], '{"1":2}');

        /** @var MockObject|Channel $channel */
        $channel = $this->getMockBuilder(Channel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channel
            ->method('ack')
            ->willReturn(TRUE);

        $result = $baseCallback->handleMessage($message->content, $message, $channel);
        $this->assertEquals($result->getStatus(), $callbackStatus);
    }

    /**
     * @return array
     */
    public function handleMessage(): array
    {
        return [
            [
                $this->getBaseCallback(NULL, CallbackStatus::SUCCESS),
                NULL,
                CallbackStatus::SUCCESS,
            ],
            [
                $this->getBaseCallback(NULL, 0),
                RabbitMqException::class,
                0,
            ],
            [
                $this->getBaseCallback(NULL, CallbackStatus::RESEND),
                NULL,
                CallbackStatus::RESEND,
            ],
            [
                $this->getBaseCallback($this->getRepeater(), CallbackStatus::RESEND),
                NULL,
                CallbackStatus::RESEND,
            ],
        ];
    }

    /**
     * @param Repeater|null $repeater
     * @param int|null      $callbackStatus
     *
     * @return SyncCallbackAbstract
     */
    public function getBaseCallback(?Repeater $repeater = NULL, ?int $callbackStatus = NULL): SyncCallbackAbstract
    {
        /** @var SyncCallbackAbstract $baseConsumer */
        $baseCallback = new class($callbackStatus) extends SyncCallbackAbstract
        {

            /**
             * @var int|null
             */
            private $callbackStatus;

            /**
             *  constructor.
             *
             * @param int|null $callbackStatus
             */
            public function __construct(?int $callbackStatus)
            {
                parent::__construct();
                $this->callbackStatus = $callbackStatus;
            }

            /**
             * @param mixed   $data
             * @param Message $message
             *
             * @return CallbackStatus
             */
            function handle($data, Message $message): CallbackStatus
            {
                $data;
                $message;

                return new CallbackStatus($this->callbackStatus);
            }

        };

        if ($repeater) {
            $baseCallback->setRepeater($repeater);
        }

        return $baseCallback;
    }

    /**
     * @return Repeater|MockObject
     */
    private function getRepeater(): MockObject
    {
        /** @var MockObject $repeater */
        $repeater = $this->getMockBuilder(Repeater::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repeater->expects($this->once())
            ->method('add')
            ->willReturn(TRUE);

        /** @var Repeater $repeater */
        return $repeater;
    }

}
