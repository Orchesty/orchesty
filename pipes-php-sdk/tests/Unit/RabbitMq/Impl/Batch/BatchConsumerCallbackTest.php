<?php declare(strict_types=1);

namespace Tests\Unit\RabbitMq\Impl\Batch;

use Exception;
use Hanaboso\CommonsBundle\Metrics\Impl\InfluxDbSender;
use Hanaboso\CommonsBundle\Metrics\MetricsSenderLoader;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Utils\Message;
use React\EventLoop\Factory;
use Throwable;
use function React\Promise\resolve;

/**
 * Class BatchConsumerCallbackTest
 *
 * @package Tests\Unit\RabbitMq\Impl\Batch
 */
final class BatchConsumerCallbackTest extends TestCase
{

    /**
     * @param mixed[] $headers
     * @param string  $content
     *
     * @return AMQPMessage
     */
    private function createMessage(array $headers = [], string $content = ''): AMQPMessage
    {
        $message = Message::create($content, $headers);
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        $message->delivery_info['delivery_tag'] = 'delivery_tag';
        // phpcs:enable

        return $message;
    }

    /**
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::validate()
     * @dataProvider validateMessageDataProvider
     *
     * @param mixed[] $headers
     * @param string  $message
     *
     * @throws Exception
     */
    public function testValidateMessage(array $headers, string $message): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        /** @var AMQPChannel|MockObject $channel */
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('basic_publish')->willReturn(resolve());
        /** @var InfluxDbSender|MockObject $influxSender */
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        /** @var Connection|MockObject $connection */
        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);

        $callback
            ->processMessage($this->createMessage($headers), $connection, 1, $loop)
            ->then(
                NULL,
                function (Exception $e) use ($loop, $message): void {
                    self::assertInstanceOf(InvalidArgumentException::class, $e);
                    self::assertSame($message, $e->getMessage());
                    $loop->stop();
                }
            )
            ->done();

        $loop->run();
    }

    /**
     * @return mixed[]
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
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage()
     *
     * @throws Exception
     */
    public function testProcessMessageBatchAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        /** @var AMQPChannel|MockObject $channel */
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare')->willReturn(resolve());
        $channel->method('basic_publish')->willReturn(resolve());
        /** @var AMQPSocketConnection|MockObject $client */
        $client = self::createMock(AMQPSocketConnection::class);
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
            ->then(
                function () use ($loop): void {
                    self::assertTrue(TRUE);

                    $loop->stop();
                },
                function (Throwable $throwable) use ($loop): void {
                    $loop->stop();

                    self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
                }
            )
            ->done();

        $loop->run();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage()
     *
     * @throws Exception
     */
    public function testProcessMessageSuccessTestAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        $batchAction->method('getBatchService')->willReturn(self::createMock(BatchInterface::class));
        /** @var AMQPChannel|MockObject $channel */
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare')->willReturn(resolve());
        $channel->method('basic_publish')->willReturn(resolve());
        /** @var AMQPSocketConnection|MockObject $client */
        $client = self::createMock(AMQPSocketConnection::class);
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
            ->then(
                function () use ($loop): void {
                    self::assertTrue(TRUE);

                    $loop->stop();
                },
                function (Throwable $throwable) use ($loop): void {
                    $loop->stop();

                    self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
                }
            )
            ->done();

        $loop->run();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage()
     *
     * @throws Exception
     */
    public function testProcessErrorMessageTestAction(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        $batchAction->method('getBatchService')->willThrowException(new Exception());
        /** @var AMQPChannel|MockObject $channel */
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare')->willReturn(resolve());
        $channel->method('basic_publish')->willReturn(resolve());
        /** @var AMQPSocketConnection|MockObject $client */
        $client = self::createMock(AMQPSocketConnection::class);
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
            ->then(
                function () use ($loop): void {
                    self::assertTrue(TRUE);

                    $loop->stop();
                },
                function (Throwable $throwable) use ($loop): void {
                    $loop->stop();

                    self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
                }
            )
            ->done();

        $loop->run();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage()
     *
     * @throws Exception
     */
    public function testProcessMessageBadType(): void
    {
        $loop = Factory::create();

        /** @var BatchActionInterface|MockObject $batchAction */
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn(resolve());
        /** @var AMQPChannel|MockObject $channel */
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare')->willReturn(resolve());
        $channel->method('basic_publish')->willReturn(resolve());
        /** @var AMQPSocketConnection|MockObject $client */
        $client = self::createMock(AMQPSocketConnection::class);
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
            ->then(
                NULL,
                function (Exception $e) use ($loop): void {
                    self::assertInstanceOf(InvalidArgumentException::class, $e);
                    self::assertSame('Unsupported type "unknown".', $e->getMessage());
                    $loop->stop();
                }
            )
            ->done();

        $loop->run();
    }

}
