<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq\Consumer;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Consumer\BaseSyncConsumerAbstract;
use Hanaboso\PipesFramework\RabbitMq\Exception\RabbitMqException;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class BaseSyncConsumerAbstractTest
 *
 * @package Tests\Unit\RabbitMq\Consumer
 */
final class BaseSyncConsumerAbstractTest extends TestCase
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
     * @param BaseSyncConsumerAbstract $baseConsumer
     * @param null|string              $exception
     *
     * @return void
     * @throws Exception
     */
    public function testHandleMessage(BaseSyncConsumerAbstract $baseConsumer, ?string $exception = NULL): void
    {
        /** @var Message $message */
        $message = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();
        /** @var Channel $channel */
        $channel = $this->getMockBuilder(Channel::class)->disableOriginalConstructor()->getMock();
        /** @var Client $client */
        $client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();

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
     * @return BaseSyncConsumerAbstract
     */
    public function getBaseConsumer(?callable $callback = NULL): BaseSyncConsumerAbstract
    {
        /** @var BaseSyncConsumerAbstract $baseConsumer */
        $baseConsumer = new class() extends BaseSyncConsumerAbstract
        {

        };

        if ($callback) {
            $baseConsumer->setCallback($callback);
        }

        return $baseConsumer;
    }

}
