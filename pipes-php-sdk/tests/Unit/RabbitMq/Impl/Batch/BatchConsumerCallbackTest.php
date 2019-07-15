<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq\Impl\Batch;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Exception;
use Hanaboso\CommonsBundle\Metrics\Impl\InfluxDbSender;
use Hanaboso\CommonsBundle\Metrics\MetricsSenderLoader;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RabbitMqBundle\Connection\Connection;
use React\EventLoop\Factory;
use function React\Promise\resolve;

/**
 * Class BatchConsumerCallbackTest
 *
 * @package Tests\Unit\RabbitMq\Impl\Batch
 */
final class BatchConsumerCallbackTest extends TestCase
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
     *
     * @throws Exception
     */
    public function testValidateMessage(array $headers, string $message): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        /** @var Channel|MockObject $channel */
        $channel = self::createMock(Channel::class);
        $channel->method('publish')->willReturn(resolve());
        /** @var InfluxDbSender|MockObject $influxSender */
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        /** @var Connection|MockObject $connection */
        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);

        $callback
            ->processMessage($this->createMessage($headers), $connection, 1, $loop)
            ->then(NULL, function (Exception $e) use ($loop, $message): void {
                self::assertInstanceOf(InvalidArgumentException::class, $e);
                self::assertSame($message, $e->getMessage());
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
                'Missing "pf-node-id" in the message header.',
            ],
            [
                [
                    'reply-to'                                    => 'reply',
                    'type'                                        => 'batch',
                    sprintf('%snode-id', PipesHeaders::PF_PREFIX) => '132',
                ],
                'Missing "pf-topology-id" in the message header.',
            ],
            [
                [
                    'reply-to'                                        => 'reply',
                    'type'                                            => 'batch',
                    sprintf('%snode-id', PipesHeaders::PF_PREFIX)     => '132',
                    sprintf('%stopology-id', PipesHeaders::PF_PREFIX) => '132',
                ],
                'Missing "pf-correlation-id" in the message header.',
            ],
            [
                [
                    'reply-to'                                           => 'reply',
                    'type'                                               => 'batch',
                    sprintf('%snode-id', PipesHeaders::PF_PREFIX)        => '132',
                    sprintf('%stopology-id', PipesHeaders::PF_PREFIX)    => '132',
                    sprintf('%scorrelation-id', PipesHeaders::PF_PREFIX) => '123',
                ],
                'Missing "pf-process-id" in the message header.',
            ],
            [
                [
                    'reply-to'                                           => 'reply',
                    'type'                                               => 'batch',
                    sprintf('%snode-id', PipesHeaders::PF_PREFIX)        => '132',
                    sprintf('%stopology-id', PipesHeaders::PF_PREFIX)    => '132',
                    sprintf('%scorrelation-id', PipesHeaders::PF_PREFIX) => '123',
                    sprintf('%sprocess-id', PipesHeaders::PF_PREFIX)     => '123',
                ],
                'Missing "pf-parent-id" in the message header.',
            ],
        ];
    }

    /**
     * @covers BatchConsumerCallback::processMessage()
     * @throws Exception
     */
    public function testProcessMessageBatchAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        /** @var Channel|MockObject $channel */
        $channel = self::createMock(Channel::class);
        $channel->method('queueDeclare')->willReturn(resolve());
        $channel->method('publish')->willReturn(resolve());
        /** @var Client|MockObject $client */
        $client = self::createMock(Client::class);
        $client->method('channel')->willReturn($channel);
        /** @var InfluxDbSender|MockObject $influxSender */
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        /** @var Connection|MockObject $connection */
        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);
        $connection->expects(self::any())->method('getClient')->willReturn($client);

        $headers = [
            'reply-to'                                            => 'reply',
            'type'                                                => 'batch',
            PipesHeaders::createKey(PipesHeaders::NODE_ID)        => '132',
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '132',
            PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '123',
            PipesHeaders::createKey(PipesHeaders::PROCESS_ID)     => '123',
            PipesHeaders::createKey(PipesHeaders::PARENT_ID)      => '',
        ];
        $callback
            ->processMessage($this->createMessage($headers), $connection, 1, $loop)
            ->then(function () use ($loop): void {
                // Test if resolve
                self::assertTrue(TRUE);
                $loop->stop();
            }, function () use ($loop): void {
                // Test if reject
                self::fail();
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers BatchConsumerCallback::processMessage()
     * @throws Exception
     */
    public function testProcessMessageSuccessTestAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        $batchAction->method('getBatchService')->willReturn(self::createMock(BatchInterface::class));
        /** @var Channel|MockObject $channel */
        $channel = self::createMock(Channel::class);
        $channel->method('queueDeclare')->willReturn(resolve());
        $channel->method('publish')->willReturn(resolve());
        /** @var Client|MockObject $client */
        $client = self::createMock(Client::class);
        $client->method('channel')->willReturn($channel);
        /** @var InfluxDbSender|MockObject $influxSender */
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        /** @var Connection|MockObject $connection */
        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);
        $connection->expects(self::any())->method('getClient')->willReturn($client);

        $headers = [
            'reply-to'                                            => 'reply',
            'type'                                                => 'test',
            PipesHeaders::createKey(PipesHeaders::NODE_ID)        => '132',
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '132',
            PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '123',
            PipesHeaders::createKey(PipesHeaders::PROCESS_ID)     => '123',
            PipesHeaders::createKey(PipesHeaders::PARENT_ID)      => '',
            PipesHeaders::createKey(PipesHeaders::NODE_NAME)      => 'test',
        ];
        $callback
            ->processMessage($this->createMessage($headers), $connection, 1, $loop)
            ->then(function () use ($loop): void {
                // Test if resolve
                self::assertTrue(TRUE);
                $loop->stop();
            }, function () use ($loop): void {
                // Test if reject
                self::fail();
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers BatchConsumerCallback::processMessage()
     * @throws Exception
     */
    public function testProcessErrorMessageTestAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        $batchAction->method('getBatchService')->willThrowException(new Exception());
        /** @var Channel|MockObject $channel */
        $channel = self::createMock(Channel::class);
        $channel->method('queueDeclare')->willReturn(resolve());
        $channel->method('publish')->willReturn(resolve());
        /** @var Client|MockObject $client */
        $client = self::createMock(Client::class);
        $client->method('channel')->willReturn($channel);
        /** @var InfluxDbSender|MockObject $influxSender */
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        /** @var Connection|MockObject $connection */
        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);
        $connection->expects(self::any())->method('getClient')->willReturn($client);

        $headers = [
            'reply-to'                                            => 'reply',
            'type'                                                => 'test',
            PipesHeaders::createKey(PipesHeaders::NODE_ID)        => '132',
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '132',
            PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '123',
            PipesHeaders::createKey(PipesHeaders::PROCESS_ID)     => '123',
            PipesHeaders::createKey(PipesHeaders::PARENT_ID)      => '',
            PipesHeaders::createKey(PipesHeaders::NODE_NAME)      => 'test',
        ];
        $callback
            ->processMessage($this->createMessage($headers), $connection, 1, $loop)
            ->then(function () use ($loop): void {
                // Test if resolve
                self::assertTrue(TRUE);
                $loop->stop();
            }, function () use ($loop): void {
                // Test if reject
                self::fail();
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

    /**
     * @covers BatchConsumerCallback::processMessage()
     * @throws Exception
     */
    public function testProcessMessageBadType(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        /** @var Channel|MockObject $channel */
        $channel = self::createMock(Channel::class);
        $channel->method('queueDeclare')->willReturn(resolve());
        $channel->method('publish')->willReturn(resolve());
        /** @var Client|MockObject $client */
        $client = self::createMock(Client::class);
        $client->method('channel')->willReturn($channel);
        /** @var InfluxDbSender|MockObject $influxSender */
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);

        $callback = new BatchConsumerCallback($batchAction, $loader);
        /** @var Connection|MockObject $connection */
        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);
        $connection->expects(self::any())->method('getClient')->willReturn($client);

        $headers = [
            'reply-to'                                            => 'reply',
            'type'                                                => 'unknown',
            PipesHeaders::createKey(PipesHeaders::NODE_ID)        => '132',
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '132',
            PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '123',
            PipesHeaders::createKey(PipesHeaders::PROCESS_ID)     => '123',
            PipesHeaders::createKey(PipesHeaders::PARENT_ID)      => '',
        ];
        $callback
            ->processMessage($this->createMessage($headers), $connection, 1, $loop)
            ->then(NULL, function (Exception $e) use ($loop): void {
                self::assertInstanceOf(InvalidArgumentException::class, $e);
                self::assertSame('Unsupported type "unknown".', $e->getMessage());
                $loop->stop();
            })
            ->done();

        $loop->run();
    }

}
