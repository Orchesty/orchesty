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
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\System\PipesHeaders;
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
 *
 * @covers  \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback
 */
final class BatchConsumerCallbackTest extends KernelTestCaseAbstract
{

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
     *
     * @dataProvider validateMessageDataProvider
     *
     * @param mixed[] $headers
     *
     * @throws Throwable
     */
    public function testValidateMessage(array $headers): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $channel     = self::createMock(AMQPChannel::class);
        $channel->expects(self::once())->method('basic_publish')->withAnyParameters();
        $logger = self::createMock(Logger::class);
        $logger->method('log');
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback     = new BatchConsumerCallback($batchAction, $loader);
        $callback->setLogger($logger);
        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);

        $callback->processMessage($this->createMessage($headers), $connection, 1);
        self::assertFake();
    }

    /**
     * @return mixed[]
     */
    public function validateMessageDataProvider(): array
    {
        return [
            [
                [],
            ],
            [
                ['reply-to' => 'reply'],
            ],
            [
                [
                    'reply-to' => 'reply',
                    'type'     => 'batch',
                ],
            ],
            [
                [
                    'reply-to'                                    => 'reply',
                    'type'                                        => 'batch',
                    sprintf('%snode-id', PipesHeaders::PF_PREFIX) => '132',
                ],
            ],
            [
                [
                    'reply-to'                                        => 'reply',
                    'type'                                            => 'batch',
                    sprintf('%snode-id', PipesHeaders::PF_PREFIX)     => '132',
                    sprintf('%stopology-id', PipesHeaders::PF_PREFIX) => '132',
                ],
            ],
            [
                [
                    'reply-to'                                           => 'reply',
                    'type'                                               => 'batch',
                    sprintf('%snode-id', PipesHeaders::PF_PREFIX)        => '132',
                    sprintf('%stopology-id', PipesHeaders::PF_PREFIX)    => '132',
                    sprintf('%scorrelation-id', PipesHeaders::PF_PREFIX) => '123',
                ],
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
     *
     * @throws Throwable
     */
    public function testProcessMessageBatchAction(): void
    {
        $batchAction = self::$container->get('hbpf.connectors.batch_connector_action_callback');

        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare');
        $channel->method('basic_publish');
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
        $callback->processMessage($this->createMessage($headers), $connection, 1);
        self::assertFake();
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
     * @throws Throwable
     */
    public function testProcessMessageSuccessTestAction(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('getBatchService')->willReturn(self::createMock(BatchInterface::class));
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare');
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
        $callback->processMessage($this->createMessage($headers), $connection, 1);
        self::assertFake();
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
     * @throws Throwable
     */
    public function testProcessErrorMessageTestAction(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('getBatchService')->willThrowException(new Exception());
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare');
        $channel->method('basic_publish');
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
        $callback->processMessage($this->createMessage($headers), $connection, 1);
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::sendMetrics
     *
     * @throws Throwable
     */
    public function testProcessErrorMetricsSend(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('getBatchService')->willThrowException(new Exception());
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare');
        $channel->method('basic_publish');
        $client = self::createMock(AMQPSocketConnection::class);
        $client->method('channel')->willReturn($channel);
        $influxSender = self::createMock(InfluxDbSender::class);
        $influxSender->method('send')->willThrowException(new Exception('err'));
        $loader     = new MetricsSenderLoader('influx', $influxSender, NULL);
        $callback   = new BatchConsumerCallback($batchAction, $loader);
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
        $callback->processMessage($this->createMessage($headers), $connection, 1);
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Throwable
     */
    public function testProcessMessageBadType(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $channel     = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare');
        $channel->method('basic_publish');
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
        $callback->processMessage($this->createMessage($headers), $connection, 1);
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Throwable
     */
    public function testProcessMessageRepeater(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willThrowException(new OnRepeatException(new ProcessDto(), 'repeated'));
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare');
        $channel->method('basic_publish');
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
        $callback->processMessage($this->createMessage($headers), $connection, 1);
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Throwable
     */
    public function testProcessMessageRepeaterLastHop(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willThrowException(new OnRepeatException(new ProcessDto(), 'repeated'));
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare');
        $channel->method('basic_publish');
        $client = self::createMock(AMQPSocketConnection::class);
        $client->method('channel')->willReturn($channel);
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);

        $callback   = new BatchConsumerCallback($batchAction, $loader);
        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);
        $connection->expects(self::any())->method('getClient')->willReturn($client);

        $headers = [
            'reply-to'                                             => 'reply',
            'type'                                                 => 'batch',
            PipesHeaders::createKey(PipesHeaders::NODE_ID)         => '132',
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)     => '132',
            PipesHeaders::createKey(PipesHeaders::CORRELATION_ID)  => '123',
            PipesHeaders::createKey(PipesHeaders::PROCESS_ID)      => '123',
            PipesHeaders::createKey(PipesHeaders::PARENT_ID)       => '',
            PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS) => '1',
            PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS)     => '1',
        ];
        $callback->processMessage($this->createMessage($headers), $connection, 1);

        $val = $this->invokeMethod($callback, 'getHeaderValue', [['a' => ['v']], 'a']);
        self::assertEquals('v', $val);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::processMessage
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::batchCallback
     * @covers \Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchConsumerCallback::itemCallback
     *
     * @throws Throwable
     */
    public function testProcessMessageRepeaterError(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willThrowException(new OnRepeatException(new ProcessDto(), 'repeated'));
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare');
        $channel->method('basic_publish');
        $client = self::createMock(AMQPSocketConnection::class);
        $client->method('channel')->willReturn($channel);
        $influxSender = self::createMock(InfluxDbSender::class);
        $loader       = new MetricsSenderLoader('influx', $influxSender, NULL);

        $callback = $this->getMockBuilder(BatchConsumerCallback::class)
            ->setConstructorArgs([$batchAction, $loader])
            ->onlyMethods(['setNextHop'])
            ->getMock();
        $callback->method('setNextHop')->willThrowException(new Exception('err'));

        $connection = self::createMock(Connection::class);
        $connection->expects(self::any())->method('getChannel')->willReturn($channel);
        $connection->expects(self::any())->method('getClient')->willReturn($client);

        $headers = [
            'reply-to'                                             => 'reply',
            'type'                                                 => 'batch',
            PipesHeaders::createKey(PipesHeaders::NODE_ID)         => '132',
            PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)     => '132',
            PipesHeaders::createKey(PipesHeaders::CORRELATION_ID)  => '123',
            PipesHeaders::createKey(PipesHeaders::PROCESS_ID)      => '123',
            PipesHeaders::createKey(PipesHeaders::PARENT_ID)       => '',
            PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS) => '1',
            PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS)     => '1',
        ];
        $callback->processMessage($this->createMessage($headers), $connection, 1);

        $val = $this->invokeMethod($callback, 'getHeaderValue', [['a' => ['v']], 'a']);
        self::assertEquals('v', $val);
    }

    /**
     * @throws Throwable
     */
    public function testProcessMessageCritical(): void
    {
        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('batchAction')->willThrowException(new Exception('err'));
        $channel = self::createMock(AMQPChannel::class);
        $channel->method('queue_declare');
        $channel->method('basic_publish');
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

        self::expectException(Exception::class);
        $callback->processMessage($this->createMessage($headers), $connection, 1);
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

        $batchAction = self::createMock(BatchActionInterface::class);
        $batchAction->method('getBatchService')->willThrowException(new Exception());

        $sender        = self::createMock(MetricsSenderLoader::class);
        $batchCallback = new BatchConsumerCallback($batchAction, $sender);

        $this->invokeMethod(
            $batchCallback,
            'testAction',
            [$channel, Message::create('', [PipesHeaders::createKey(PipesHeaders::NODE_NAME) => 'name'])],
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
        $batchCallback = self::$container->get('hbpf.connectors.batch_connector_callback');

        $channel = self::createMock(AMQPChannel::class);
        $channel->method('basic_publish');

        $this->invokeMethod(
            $batchCallback,
            'itemCallback',
            [
                $channel,
                new AMQPMessage(),
                (new SuccessMessage(2))->setResultCode(1_004),
            ],
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
        $batchCallback = self::$container->get('hbpf.connectors.batch_connector_callback');

        $channel = self::createMock(AMQPChannel::class);
        $channel->method('basic_publish');

        $this->invokeMethod($batchCallback, 'itemCallback', [$channel, new AMQPMessage(), new SuccessMessage(2)]);

        self::assertFake();
    }

    /**
     * @param mixed[] $headers
     *
     * @return AMQPMessage
     */
    private function createMessage(array $headers = []): AMQPMessage
    {
        $message = Message::create('', $headers);
        $message->setDeliveryTag(1);

        return $message;
    }

}
