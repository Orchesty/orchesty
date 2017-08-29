<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 30.8.17
 * Time: 20:37
 */

namespace Tests\Unit\Commons\RabbitMq;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Hanaboso\PipesFramework\Commons\RabbitMq\BaseConsumerAbstract;
use Hanaboso\PipesFramework\Commons\RabbitMq\Exception\RabbitMqException;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class BaseConsumerAbstractTest
 *
 * @package Tests\Unit\Commons\RabbitMq
 */
class BaseConsumerAbstractTest extends TestCase
{

    /**
     * @dataProvider setCallback
     * @covers       BaseConsumerAbstract::setCallback()
     *
     * @param callable    $callback
     * @param null|string $exception
     */
    public function testSetCallback($callback, $exception = NULL)
    {
        if ($exception) {
            $this->expectException($exception);
        }

        $baseConsumer = $this->getBaseConsumer();
        $baseConsumer->setCallback($callback);
    }

    /**
     * @dataProvider handleMessage
     * @covers       BaseConsumerAbstract::handleMessage()
     *
     * @param BaseConsumerAbstract $baseConsumer
     * @param null|string          $exception
     */
    public function testHandleMessage(BaseConsumerAbstract $baseConsumer, ?string $exception = NULL)
    {
        $message = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();
        $channel = $this->getMockBuilder(Channel::class)->disableOriginalConstructor()->getMock();
        $client  = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();

        if ($exception) {
            $this->expectException($exception);
        }
        $baseConsumer->handleMessage('', $message, $channel, $client);
    }

    /**
     * @return array
     */
    public function handleMessage(): array
    {
        return [
            [$this->getBaseConsumer(), RabbitMqException::class],
            [
                $this->getBaseConsumer(function () {
                }), NULL,
            ],
        ];
    }

    /**
     * @return array
     */
    public function setCallback(): array
    {
        return [
            [
                (function () {
                }), NULL,
            ],
            [
                NULL, TypeError::class,
            ],
            [
                [
                    new class
                    {

                        public function get()
                        {
                        }
                    }, 'get',
                ],
                NULL,
            ],
        ];
    }

    /**
     * @param callable|null $callback
     *
     * @return BaseConsumerAbstract
     */
    public function getBaseConsumer(?callable $callback = NULL): BaseConsumerAbstract
    {
        /** @var BaseConsumerAbstract $baseConsumer */
        $baseConsumer = new class() extends BaseConsumerAbstract
        {

        };

        if ($callback) {
            $baseConsumer->setCallback($callback);
        }

        return $baseConsumer;
    }

}
