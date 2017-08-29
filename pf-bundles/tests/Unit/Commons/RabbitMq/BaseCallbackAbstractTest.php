<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 31.8.17
 * Time: 22:01
 */

namespace Tests\Unit\Commons\RabbitMq;

use Bunny\Message;
use Hanaboso\PipesFramework\Commons\RabbitMq\BaseCallbackAbstract;
use Hanaboso\PipesFramework\Commons\RabbitMq\CallbackStatus;
use PHPUnit\Framework\TestCase;

class BaseCallbackAbstractTest extends TestCase
{

    /**
     * @dataProvider handleMessage
     * @covers       BaseCallbackAbstract::handleMessage()
     *
     * @param BaseCallbackAbstract $callback
     */
    public function testHandleMessage(BaseCallbackAbstract $callback, $data, $message, $exception)
    {
        $baseCallback = $this->getBaseCallback();

        $baseCallback->handleMessage($data, $message);
    }

    /**
     * @return array
     */
    public function handleMessage(): array
    {
        return [
            [],
        ];
    }

    /**
     * @param null     $repeater
     *
     * @param int|null $callbackStatus
     *
     * @return BaseCallbackAbstract
     */
    public function getBaseCallback($repeater = NULL, ?int $callbackStatus = NULL): BaseCallbackAbstract
    {
        /** @var BaseCallbackAbstract $baseConsumer */
        $baseCallback = new class($callbackStatus) extends BaseCallbackAbstract
        {

            private $callbackStatus;

            /**
             *  constructor.
             *
             * @param int|null $callbackStatus
             */
            public function __construct(?int $callbackStatus)
            {
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

}
