<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 31.8.17
 * Time: 22:01
 */

namespace Tests\Unit\RabbitMq\Base;

use Bunny\Channel;
use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\Base\BaseCallbackAbstract;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Exception\RabbitMqException;
use Hanaboso\PipesFramework\RabbitMq\Repeater\Repeater;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class BaseCallbackAbstractTest
 *
 * @package Tests\Unit\RabbitMq\Base
 */
class BaseCallbackAbstractTest extends TestCase
{

    /**
     * @dataProvider handleMessage
     * @covers       BaseCallbackAbstract::handleMessage()
     *
     * @param BaseCallbackAbstract $baseCallback
     * @param string|null          $exception
     * @param int|null             $callbackStatus
     *
     * @return void
     */
    public function testHandleMessage(
        BaseCallbackAbstract $baseCallback,
        ?string $exception,
        ?int $callbackStatus
    ): void
    {
        if ($exception) {
            $this->expectException($exception);
        }
        $message = new Message('', '', FALSE, 'test', 'key', [], '{"1":2}');

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
     * @return BaseCallbackAbstract
     */
    public function getBaseCallback(?Repeater $repeater = NULL, ?int $callbackStatus = NULL): BaseCallbackAbstract
    {
        /** @var BaseCallbackAbstract $baseConsumer */
        $baseCallback = new class($callbackStatus) extends BaseCallbackAbstract
        {

            /** @var int|null */
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
                return new CallbackStatus($this->callbackStatus);
            }
        };

        if ($repeater) {
            $baseCallback->setRepeater($repeater);
        }

        return $baseCallback;
    }

    /**
     * @return Repeater|PHPUnit_Framework_MockObject_MockObject
     */
    private function getRepeater(): PHPUnit_Framework_MockObject_MockObject
    {
        /** @var PHPUnit_Framework_MockObject_MockObject $repeater */
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
