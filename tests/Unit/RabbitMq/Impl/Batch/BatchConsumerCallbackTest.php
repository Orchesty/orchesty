<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/3/17
 * Time: 3:49 PM
 */

namespace Tests\Unit\RabbitMq\Impl\Batch;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchActionInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchConsumerCallback;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use function React\Promise\resolve;

/**
 * Class BatchCallbackTest
 *
 * @package Tests\Unit\RabbitMq\Impl\Batch
 */
class BatchConsumerCallbackTest extends TestCase
{

    /**
     * @param array  $headers
     * @param string $content
     *
     * @return Message
     */
    private function createMessage(array $headers = [], string $content = ''): Message
    {
        return new Message(
            'consumer_tag',
            'delivery_tag',
            FALSE,
            'exchange',
            'routing_key',
            $headers,
            $content
        );
    }

    /**
     * @covers       BatchConsumerCallback::validate()
     * @dataProvider validateMessageDataProvider
     *
     * @param array  $headers
     * @param string $message
     */
    public function testValidateMessage(array $headers, string $message): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|PHPUnit_Framework_MockObject_MockObject $batchAction */
        $batchAction = $this->createMock(BatchActionInterface::class);
        /** @var Channel|PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->method('publish')->willReturn(resolve());
        /** @var Client|PHPUnit_Framework_MockObject_MockObject $client */
        $client   = $this->createMock(Client::class);
        $callback = new BatchConsumerCallback($batchAction);

        $callback
            ->processMessage($this->createMessage($headers), $channel, $client, $loop)
            ->then(NULL, function (Exception $e) use ($loop, $message): void {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
                $this->assertSame($message, $e->getMessage());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @return array
     */
    public function validateMessageDataProvider(): array
    {
        return [
            [
                [],
                'Missing "reply-to" in the message header.',
            ],
            [
                ['reply-to' => 'reply'],
                'Missing "type" in the message header.',
            ],
            [
                [
                    'reply-to' => 'reply',
                    'type'     => 'batch',
                ],
                'Missing "node-id" in the message header.',
            ],
            [
                [
                    'reply-to'                          => 'reply',
                    'type'                              => 'batch',
                    PipesHeaders::PF_PREFIX . 'node-id' => '132',
                ],
                'Missing "correlation-id" in the message header.',
            ],
            [
                [
                    'reply-to'                                 => 'reply',
                    'type'                                     => 'batch',
                    PipesHeaders::PF_PREFIX . 'node-id'        => '132',
                    PipesHeaders::PF_PREFIX . 'correlation-id' => '123',
                ],
                'Missing "process-id" in the message header.',
            ],
        ];
    }

    /**
     * @covers BatchConsumerCallback::processMessage()
     */
    public function testProcessMessageBatchAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|PHPUnit_Framework_MockObject_MockObject $batchAction */
        $batchAction = $this->createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        /** @var Channel|PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->method('queueDeclare')->willReturn(resolve());
        $channel->method('publish')->willReturn(resolve());
        /** @var Client|PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(Client::class);
        $client->method('channel')->willReturn($channel);

        $callback = new BatchConsumerCallback($batchAction);

        $headers = [
            'reply-to'                                 => 'reply',
            'type'                                     => 'batch',
            PipesHeaders::PF_PREFIX . 'node-id'        => '132',
            PipesHeaders::PF_PREFIX . 'correlation-id' => '123',
            PipesHeaders::PF_PREFIX . 'process-id'     => '123',
        ];
        $callback
            ->processMessage($this->createMessage($headers), $channel, $client, $loop)
            ->then(function () use ($loop): void {
                // Test if resolve
                $this->assertTrue(TRUE);
                $loop->stop();
            }, function () use ($loop): void {
                // Test if reject
                $this->assertTrue(FALSE);
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers BatchConsumerCallback::processMessage()
     */
    public function testProcessMessageTestAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|PHPUnit_Framework_MockObject_MockObject $batchAction */
        $batchAction = $this->createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        /** @var Channel|PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->method('queueDeclare')->willReturn(resolve());
        $channel->method('publish')->willReturn(resolve());
        /** @var Client|PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(Client::class);
        $client->method('channel')->willReturn($channel);

        $callback = new BatchConsumerCallback($batchAction);

        $headers = [
            'reply-to'                                 => 'reply',
            'type'                                     => 'test',
            PipesHeaders::PF_PREFIX . 'node-id'        => '132',
            PipesHeaders::PF_PREFIX . 'correlation-id' => '123',
            PipesHeaders::PF_PREFIX . 'process-id'     => '123',
        ];
        $callback
            ->processMessage($this->createMessage($headers), $channel, $client, $loop)
            ->then(function () use ($loop): void {
                // Test if resolve
                $this->assertTrue(TRUE);
                $loop->stop();
            }, function () use ($loop): void {
                // Test if reject
                $this->assertTrue(FALSE);
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers BatchConsumerCallback::processMessage()
     */
    public function testProcessMessageBadType(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|PHPUnit_Framework_MockObject_MockObject $batchAction */
        $batchAction = $this->createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        /** @var Channel|PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->method('queueDeclare')->willReturn(resolve());
        $channel->method('publish')->willReturn(resolve());
        /** @var Client|PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(Client::class);
        $client->method('channel')->willReturn($channel);

        $callback = new BatchConsumerCallback($batchAction);

        $headers = [
            'reply-to'                                 => 'reply',
            'type'                                     => 'unknown',
            PipesHeaders::PF_PREFIX . 'node-id'        => '132',
            PipesHeaders::PF_PREFIX . 'correlation-id' => '123',
            PipesHeaders::PF_PREFIX . 'process-id'     => '123',
        ];
        $callback
            ->processMessage($this->createMessage($headers), $channel, $client, $loop)
            ->then(NULL, function (Exception $e) use ($loop): void {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
                $this->assertSame('Unsupported type "unknown".', $e->getMessage());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

}