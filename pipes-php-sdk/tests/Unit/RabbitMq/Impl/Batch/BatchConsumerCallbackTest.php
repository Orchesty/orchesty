<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Metrics\Impl\InfluxDbSender;
use Hanaboso\CommonsBundle\Metrics\MetricsSenderLoader;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchActionInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\System\PipesHeaders;
use InvalidArgumentException;
use Monolog\Logger;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Utils\Message;
use Throwable;

/**
 * Class BatchConsumerCallbackTest
 *
 * @package PipesPhpSdkTests\Unit\RabbitMq\Impl\Batch
 */
final class BatchConsumerCallbackTest extends KernelTestCaseAbstract
{

    use BatchTrait;

    /**
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::setLogger
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::validate()
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage()
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::testAction()
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::publishErrorTestMessage()
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchAction()
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::publishSuccessTestMessage()
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback()
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback()
     * @covers       \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::alwaysCallback()
     *
     * @dataProvider validateMessageDataProvider
     *
     * @param mixed[] $headers
     * @param string  $message
     *
     * @throws Exception
     */
    public function testValidateMessage(array $headers, string $message): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $channel     = self::createMock(AMQPChannel::class);
        $channel->method('basic_publish')->willReturn($this->createPromise());
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        $callback->setLogger(new Logger('logger'));
        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);

        $callback
            ->processMessage($this->createMessage($headers), $connection, 1)
            ->then(
                NULL,
                static function ($e) use ($message): void {
                    self::assertInstanceOf(InvalidArgumentException::class, $e);
                    self::assertSame($message, $e->getMessage());
                }
            )->wait();
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
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::testAction
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::publishErrorTestMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchAction
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::publishSuccessTestMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::alwaysCallback
     *
     * @throws Exception
     */
    public function testProcessMessageBatchAction(): void
    {
        $batchAction = self::$container->get('hbpf.custom_nodes.batch_action_callback');

        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare')->willReturn($this->createPromise());
        $channel->method('basic_publish')->willReturn($this->createPromise());
        $client = self::createMock(AMQPSocketConnection::class);
        $client->method('channel')->willReturn($channel);
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        $connection   = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);
        $connection->expects(self::any())->method('getClient')->willReturn($client);

        $headers = [
            'reply-to'                                            => 'reply',
            'type'                                                => 'batch',
            PipesHeaders::createKey(PipesHeaders::NODE_ID)        => 'batch-null',
            PipesHeaders::createKey(PipesHeaders::NODE_NAME)      => 'batch-null',
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)    => '132',
            PipesHeaders::createKey(PipesHeaders::CORRELATION_ID) => '123',
            PipesHeaders::createKey(PipesHeaders::PROCESS_ID)     => '123',
            PipesHeaders::createKey(PipesHeaders::PARENT_ID)      => '',
        ];
        $callback
            ->processMessage($this->createMessage($headers), $connection, 1)
            ->then(
                static function (): void {
                    self::assertTrue(TRUE);
                },
                static function (Throwable $throwable): void {
                    self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
                }
            )->wait();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::testAction
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::publishErrorTestMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchAction
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::publishSuccessTestMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Exception
     */
    public function testProcessMessageSuccessTestAction(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn($this->createPromise());
        $batchAction->method('getBatchService')->willReturn(self::createMock(BatchInterface::class));
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare')->willReturn($this->createPromise());
        $channel->method('basic_publish')->willReturn($this->createPromise());
        $client = self::createMock(AMQPSocketConnection::class);
        $client->method('channel')->willReturn($channel);
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        $connection   = self::createMock(Connection::class);
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
            ->processMessage($this->createMessage($headers), $connection, 1)
            ->then(
                static function (): void {
                    self::assertTrue(TRUE);
                },
                static function (Throwable $throwable): void {
                    self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
                }
            )->wait();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::validate
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::publishErrorTestMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchAction
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::publishSuccessTestMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Exception
     */
    public function testProcessErrorMessageTestAction(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn($this->createPromise());
        $batchAction->method('getBatchService')->willThrowException(new Exception());
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare')->willReturn($this->createPromise());
        $channel->method('basic_publish')->willReturn($this->createPromise());
        $client = self::createMock(AMQPSocketConnection::class);
        $client->method('channel')->willReturn($channel);
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        $connection   = self::createMock(Connection::class);
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
            ->processMessage($this->createMessage($headers), $connection, 1)
            ->then(
                static function (): void {
                    self::assertTrue(TRUE);
                },
                static function (Throwable $throwable): void {
                    self::fail(sprintf('%s%s%s', $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));
                }
            )->wait();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Exception
     */
    public function testProcessMessageBadType(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willReturn($this->createPromise());
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare')->willReturn($this->createPromise());
        $channel->method('basic_publish')->willReturn($this->createPromise());
        $client = self::createMock(AMQPSocketConnection::class);
        $client->method('channel')->willReturn($channel);
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);

        $callback   = new BatchConsumerCallback($batchAction, $loader);
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
            ->processMessage($this->createMessage($headers), $connection, 1)
            ->then(
                NULL,
                static function (Exception $e): void {
                    self::assertInstanceOf(InvalidArgumentException::class, $e);
                    self::assertSame('Unsupported type "unknown".', $e->getMessage());
                }
            )->wait();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Exception
     */
    public function testProcessMessageRepeater(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willThrowException(new OnRepeatException(new ProcessDto(),'repeated'));
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare')->willReturn($this->createPromise());
        $channel->method('basic_publish')->willReturn($this->createPromise());
        $client = self::createMock(AMQPSocketConnection::class);
        $client->method('channel')->willReturn($channel);
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);

        $callback   = new BatchConsumerCallback($batchAction, $loader);
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
            ->processMessage($this->createMessage($headers), $connection, 1)
            ->then(
                NULL,
                static function (Exception $e): void {
                    self::assertInstanceOf(OnRepeatException::class, $e);
                    self::assertSame('repeated', $e->getMessage());
                }
            )->wait();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::publishErrorTestMessage
     */
    public function testPublishErrorTestMessage(): void
    {
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('basic_publish');

        $callback = self::$container->get('hbpf.custom_nodes.batch_callback');
        $callback->publishErrorTestMessage($channel, new AMQPMessage(), new Exception());

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::testAction
     *
     * @throws Exception
     */
    public function testActionErr(): void
    {
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('basic_publish');

        $batchAction = self::createMock(BatchActionInterface::class,);
        $batchAction->method('getBatchService')->willThrowException(new Exception());

        $sender        = self::createMock(MetricsSenderLoader::class);
        $batchCallback = new BatchConsumerCallback($batchAction, $sender);

        $this->invokeMethod(
            $batchCallback,
            'testAction',
            [$channel, new AMQPMessage(), [PipesHeaders::createKey(PipesHeaders::NODE_NAME) => 'name']]
        );
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Exception
     */
    public function testItemCallbackWithResultCode(): void
    {
        $batchCallback = self::$container->get('hbpf.custom_nodes.batch_callback');

        $channel = self::createMock(AMQPChannel::class);
        $channel->method('basic_publish');

        $this->invokeMethod(
            $batchCallback,
            'itemCallback',
            [
                $channel,
                new AMQPMessage(),
                (new SuccessMessage(2))->setResultCode(1_004),
            ]
        );

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Exception
     */
    public function testItemCallback(): void
    {
        $batchCallback = self::$container->get('hbpf.custom_nodes.batch_callback');

        $channel = self::createMock(AMQPChannel::class);
        $channel->method('basic_publish');

        $this->invokeMethod($batchCallback, 'itemCallback', [$channel, new AMQPMessage(), new SuccessMessage(2)]);

        self::assertFake();
    }

    /**
     * @param mixed[] $headers
     * @param string  $content
     *
     * @return AMQPMessage
     */
    private function createMessage(array $headers = [], string $content = ''): AMQPMessage
    {
        $message = Message::create($content, $headers);
        $message->setDeliveryTag(1);

        return $message;
    }

}
