<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use Exception;
use Hanaboso\CommonsBundle\Enum\MetricsEnum;
use Hanaboso\CommonsBundle\Metrics\MetricsSenderLoader;
use Hanaboso\CommonsBundle\Utils\CurlMetricUtils;
use Hanaboso\Utils\System\PipesHeaders;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\AsyncCallbackInterface;
use RabbitMqBundle\Consumer\DebugMessageTrait;
use RabbitMqBundle\Utils\Message;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use Throwable;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class BatchConsumerCallback
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
class BatchConsumerCallback implements AsyncCallbackInterface, LoggerAwareInterface
{

    use DebugMessageTrait;

    // Properties
    private const REPLY_TO       = 'reply-to';
    private const TYPE           = 'type';
    private const MISSING_HEADER = 'Missing "%s" in the message header.';

    /**
     * @var BatchActionInterface
     */
    private $batchAction;

    /**
     * @var MetricsSenderLoader
     */
    private $sender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var mixed[]
     */
    private $currentMetrics = [];

    /**
     * BatchConsumerCallback constructor.
     *
     * @param BatchActionInterface $batchAction
     * @param MetricsSenderLoader  $sender
     */
    public function __construct(BatchActionInterface $batchAction, MetricsSenderLoader $sender)
    {
        $this->batchAction = $batchAction;
        $this->sender      = $sender;
        $this->logger      = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     *
     */
    private function startMetrics(): void
    {
        $this->currentMetrics = CurlMetricUtils::getCurrentMetrics();
    }

    /**
     * @param AMQPMessage $message
     *
     * @return PromiseInterface
     */
    private function validate(AMQPMessage $message): PromiseInterface
    {
        $headers = Message::getHeaders($message);

        if ($this->isEmpty($headers[self::REPLY_TO] ?? '')) {
            return reject(new InvalidArgumentException(sprintf(self::MISSING_HEADER, self::REPLY_TO)));
        }

        if ($this->isEmpty($headers[self::TYPE] ?? '')) {
            return reject(new InvalidArgumentException(sprintf(self::MISSING_HEADER, self::TYPE)));
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_ID, $headers))) {
            return reject(
                new InvalidArgumentException(
                    sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::NODE_ID))
                )
            );
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $headers))) {
            return reject(
                new InvalidArgumentException(
                    sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID))
                )
            );
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::CORRELATION_ID, $headers))) {
            return reject(
                new InvalidArgumentException(
                    sprintf(
                        self::MISSING_HEADER,
                        PipesHeaders::createKey(PipesHeaders::CORRELATION_ID)
                    )
                )
            );
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::PROCESS_ID, $headers))) {
            return reject(
                new InvalidArgumentException(
                    sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::PROCESS_ID))
                )
            );
        }

        if (!array_key_exists(PipesHeaders::createKey(PipesHeaders::PARENT_ID), $headers)) {
            return reject(
                new InvalidArgumentException(
                    sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::PARENT_ID))
                )
            );
        }

        return resolve();
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    private function isEmpty(?string $value): bool
    {
        return $value === '' || $value === NULL;
    }

    /**
     * @param AMQPMessage   $message
     * @param Connection    $connection
     * @param int           $channelId
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     */
    public function processMessage(
        AMQPMessage $message,
        Connection $connection,
        int $channelId,
        LoopInterface $loop
    ): PromiseInterface
    {
        $this->startMetrics();

        // @todo use class property - array of channels ?
        /** @var AMQPChannel|null $replyChannel */
        $replyChannel = NULL;
        $headers      = Message::getHeaders($message);

        return $this
            ->validate($message)
            ->then(
                function () use ($message, $headers): void {
                    $this->logger->debug(
                        'Batch consumer received message',
                        array_merge(
                            $this->prepareBunnyMessage($message),
                            PipesHeaders::debugInfo($headers)
                        )
                    );
                }
            )
            ->then(static fn(): AMQPChannel => $connection->getClient()->channel())
            ->then(
                static function (AMQPChannel $channel) use ($headers, &$replyChannel): AMQPChannel {
                    $replyChannel = $channel;

                    $channel->queue_declare($headers[self::REPLY_TO] ?? '', FALSE, FALSE, FALSE, FALSE);

                    return $channel;
                }
            )
            ->then(
                function (AMQPChannel $channel) use ($message, $headers, $loop) {
                    switch ($headers[self::TYPE]) {
                        case 'test':
                            return $this->testAction($channel, $message, $headers);
                        case 'batch':
                            return $this->batchAction($message, $channel, $loop);
                        default:
                            return reject(
                                new InvalidArgumentException(sprintf('Unsupported type "%s".', $headers[self::TYPE]))
                            );
                    }
                }
            )->otherwise(
                function (Throwable $e) use ($replyChannel, $connection, $channelId, $message, $headers) {
                    if ($replyChannel === NULL) {
                        // @todo create new channel
                        $replyChannel = $connection->getChannel($channelId);
                    }

                    return $this
                        ->batchErrorCallback(
                            $replyChannel,
                            $message,
                            new ErrorMessage(2_001, $e->getMessage())
                        )
                        ->then(
                            function () use ($e, $headers) {
                                $this->logger->error(
                                    sprintf('Batch action error: %s', $e->getMessage()),
                                    array_merge(['exception' => $e], PipesHeaders::debugInfo($headers))
                                );

                                return reject($e);
                            }
                        );
                }
            )->always(
                function () use ($message, &$replyChannel, $connection, $channelId): void {
                    /** @var AMQPChannel|null $channel */
                    $channel      = $replyChannel;
                    $replyChannel = $channel;
                    Message::ack($message, $connection, $channelId);

                    if ($replyChannel !== NULL) {
                        $replyChannel->close();
                        unset($replyChannel);
                        unset($channel);
                    }

                    $this->sendMetrics($message, $this->currentMetrics);
                }
            );
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     * @param mixed[]     $headers
     *
     * @return PromiseInterface
     */
    private function testAction(AMQPChannel $channel, AMQPMessage $message, array $headers): PromiseInterface
    {
        try {
            /** @var string $nodeName */
            $nodeName = PipesHeaders::get(PipesHeaders::NODE_NAME, $headers);
            $this->batchAction->getBatchService($nodeName);

            return $this->publishSuccessTestMessage($channel, $headers);
        } catch (Exception $e) {
            return $this->publishErrorTestMessage($channel, $message, $e);
        }
    }

    /**
     * @param AMQPChannel $channel
     * @param mixed[]     $headers
     *
     * @return PromiseInterface
     */
    private function publishSuccessTestMessage(AMQPChannel $channel, array $headers): PromiseInterface
    {
        $headers = array_merge($headers, [PipesHeaders::createKey(PipesHeaders::RESULT_CODE) => 0]);
        $channel->basic_publish(Message::create('', $headers), '', $headers[self::REPLY_TO] ?? '');

        return (new Promise(static fn(callable $resolve) => $resolve()))->then(
            function () use ($headers): void {
                $this->logger->debug(
                    'Published test item.',
                    array_merge(
                        $this->prepareMessage('', '', $headers[self::REPLY_TO] ?? '', $headers),
                        PipesHeaders::debugInfo($headers)
                    )
                );
            }
        );
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     * @param Exception   $e
     *
     * @return PromiseInterface
     */
    public function publishErrorTestMessage(AMQPChannel $channel, AMQPMessage $message, Exception $e): PromiseInterface
    {
        $headers = Message::getHeaders($message);

        $headers = array_merge(
            $headers,
            [
                PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => 2_001,
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $e->getMessage(),
            ]
        );

        $channel->basic_publish(Message::create('', $headers), '', $headers[self::REPLY_TO] ?? '');

        return (new Promise(static fn(callable $resolve) => $resolve()))->then(
            function () use ($headers): void {
                $this->logger->error(
                    'Published test item error.',
                    array_merge(
                        $this->prepareMessage('', '', $headers[self::REPLY_TO] ?? '', $headers),
                        PipesHeaders::debugInfo($headers)
                    )
                );
            }
        );
    }

    /**
     * @param AMQPMessage   $message
     * @param AMQPChannel   $channel
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     * @internal param Channel $channel
     */
    private function batchAction(AMQPMessage $message, AMQPChannel $channel, LoopInterface $loop): PromiseInterface
    {
        return $this->batchAction->batchAction(
            $message,
            $loop,
            fn(SuccessMessage $successMessage) => $this->itemCallback($channel, $message, $successMessage)
        )->then(fn() => $this->batchCallback($channel, $message));
    }

    /**
     * @param AMQPChannel    $channel
     * @param AMQPMessage    $message
     * @param SuccessMessage $successMessage
     *
     * @return PromiseInterface
     */
    private function itemCallback(
        AMQPChannel $channel,
        AMQPMessage $message,
        SuccessMessage $successMessage
    ): PromiseInterface
    {
        $headers = Message::getHeaders($message);

        // Limiter
        if ($successMessage->hasHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE)) &&
            $successMessage->getHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE)) == 1_004
        ) {
            $message->set(
                Message::APPLICATION_HEADERS,
                new AMQPTable(
                    array_merge($headers[Message::APPLICATION_HEADERS] ?? [], [PipesHeaders::RESULT_CODE => 1_004])
                )
            );

            return resolve();
        }

        $resultMessage = sprintf(
            'Batch item %s for node %s.',
            $successMessage->getSequenceId(),
            PipesHeaders::get(PipesHeaders::NODE_NAME, $headers)
        );

        $headers = array_merge(
            $headers,
            $successMessage->getHeaders(),
            [
                self::TYPE                                            => 'batch_item',
                PipesHeaders::createKey(PipesHeaders::SEQUENCE_ID)    => $successMessage->getSequenceId(),
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $resultMessage,
                PipesHeaders::createKey(PipesHeaders::TIMESTAMP)      => (string) round(microtime(TRUE) * 1_000),
            ]
        );

        $channel->basic_publish(
            Message::create($successMessage->getData(), $headers),
            '',
            $headers[self::REPLY_TO] ?? ''
        );

        return (new Promise(static fn(callable $resolve) => $resolve()))->then(
            function () use ($successMessage, $headers): void {
                $this->logger->debug(
                    sprintf('Published batch item %s.', $successMessage->getSequenceId()),
                    array_merge(
                        $this->prepareMessage('', '', $headers[self::REPLY_TO] ?? '', $headers),
                        PipesHeaders::debugInfo($headers)
                    )
                );
            }
        );
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     *
     * @return PromiseInterface
     * @throws Exception
     */
    private function batchCallback(AMQPChannel $channel, AMQPMessage $message): PromiseInterface
    {
        $headers       = Message::getHeaders($message);
        $resultMessage = sprintf(
            'Batch end for node %s.',
            PipesHeaders::get(PipesHeaders::NODE_NAME, $headers)
        );

        $headers = array_merge(
            $headers,
            [
                self::TYPE                                            => 'batch_end',
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $resultMessage,
            ]
        );

        if (!($headers[PipesHeaders::createKey(PipesHeaders::RESULT_CODE)] ?? '')) {
            $headers[PipesHeaders::createKey(PipesHeaders::RESULT_CODE)] = 0;
        }

        $channel->basic_publish(Message::create('', $headers), '', $headers[self::REPLY_TO] ?? '');

        return (new Promise(static fn(callable $resolve) => $resolve()))->then(
            function () use ($headers): void {
                $this->logger->debug(
                    'Published batch end.',
                    array_merge(
                        $this->prepareMessage('', '', $headers[self::REPLY_TO] ?? '', $headers),
                        PipesHeaders::debugInfo($headers)
                    )
                );
            }
        );
    }

    /**
     * @param AMQPChannel  $channel
     * @param AMQPMessage  $message
     *
     * @param ErrorMessage $errorMessage
     *
     * @return PromiseInterface
     */
    private function batchErrorCallback(
        AMQPChannel $channel,
        AMQPMessage $message,
        ErrorMessage $errorMessage
    ): PromiseInterface
    {
        $headers = Message::getHeaders($message);
        $headers = array_merge(
            $headers,
            [
                self::TYPE                                            => 'batch_end',
                PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => $errorMessage->getCode(),
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $errorMessage->getMessage(),
                PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => $errorMessage->getDetail(),
            ]
        );

        $channel->basic_publish(Message::create('', $headers), '', $headers[self::REPLY_TO] ?? '');

        return (new Promise(static fn(callable $resolve) => $resolve()))->then(
            function () use ($headers): void {
                $this->logger->error(
                    'Published batch error end.',
                    array_merge(
                        $this->prepareMessage('', '', $headers[self::REPLY_TO] ?? '', $headers),
                        PipesHeaders::debugInfo($headers)
                    )
                );
            }
        );
    }

    /**
     * @param AMQPMessage $message
     * @param mixed[]     $startMetrics
     *
     * @throws Exception
     */
    private function sendMetrics(AMQPMessage $message, array $startMetrics): void
    {
        $headers = Message::getHeaders($message);

        $times = CurlMetricUtils::getTimes($startMetrics);
        $this->sender->getSender()->send(
            [
                MetricsEnum::REQUEST_TOTAL_DURATION => $times[CurlMetricUtils::KEY_REQUEST_DURATION],
                MetricsEnum::CPU_USER_TIME          => $times[CurlMetricUtils::KEY_USER_TIME],
                MetricsEnum::CPU_KERNEL_TIME        => $times[CurlMetricUtils::KEY_KERNEL_TIME],
            ],
            [
                MetricsEnum::HOST           => gethostname(),
                MetricsEnum::TOPOLOGY_ID    => $headers[PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)] ?? NULL,
                MetricsEnum::CORRELATION_ID => $headers[PipesHeaders::createKey(PipesHeaders::CORRELATION_ID)] ?? NULL,
                MetricsEnum::NODE_ID        => $headers[PipesHeaders::createKey(PipesHeaders::NODE_ID)] ?? NULL,
            ]
        );
    }

}
