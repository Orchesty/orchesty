<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 30.8.17
 * Time: 20:37
 */

namespace Tests\Unit\RabbitMq\Base;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\Base\BaseConsumerAbstract;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Exception\RabbitMqException;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class BaseConsumerAbstractTest
 *
 * @package Tests\Unit\RabbitMq\Base
 */
class BaseConsumerAbstractTest extends TestCase
{

    /**
     * @dataProvider setCallback
     * @covers       BaseConsumerBaseAbstract::setCallback()
     *
     * @param callable    $callback
     * @param null|string $exception
     *
     * @return void
     */
    public function testSetCallback($callback, $exception = NULL): void
    {
        if ($exception) {
            $this->expectException($exception);
        }

        $baseConsumer = $this->getBaseConsumer();
        $baseConsumer->setCallback($callback);
    }

    /**
     * @dataProvider handleMessage
     * @covers       BaseConsumerBaseAbstract::handleMessage()
     *
     * @param BaseConsumerAbstract $baseConsumer
     * @param null|string          $exception
     *
     * @return void
     */
    public function testHandleMessage(BaseConsumerAbstract $baseConsumer, ?string $exception = NULL): void
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
                    return new CallbackStatus(CallbackStatus::SUCCESS);
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
                (function (): void {
                }), NULL,
            ],
            [
                NULL, TypeError::class,
            ],
            [
                [
                    new class
                    {

                        /**
                         * @return void
                         */
                        public function get(): void
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
