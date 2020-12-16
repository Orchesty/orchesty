<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Hanaboso\CommonsBundle\Enum\MetricsEnum;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Metrics\MetricsSenderLoader;
use Hanaboso\CommonsBundle\Utils\CurlMetricUtils;
use Hanaboso\PipesPhpSdk\Utils\RepeaterTrait;
use Hanaboso\Utils\System\PipesHeaders;
use Hanaboso\Utils\Traits\LoggerTrait;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\AsyncCallbackInterface;
use RabbitMqBundle\Consumer\DebugMessageTrait;
use RabbitMqBundle\Utils\Message;
use Throwable;

/**
 * Class BatchConsumerCallback
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
class BatchConsumerCallback implements AsyncCallbackInterface, LoggerAwareInterface
{

    use DebugMessageTrait;
    use BatchTrait;
    use RepeaterTrait;
    use LoggerTrait;

    // Properties
    protected const  REPLY_TO          = 'reply-to';
    protected const  TYPE              = 'type';
    protected const  PERSISTENCE       = 'delivery-mode';
    protected const  MISSING_HEADER    = 'Missing "%s" in the message header.';
    protected const  BATCH_END_TYPE    = 'batch_end';
    protected const  BATCH_REPEAT_TYPE = 'batch_repeat';

    /**
     * @var BatchActionInterface
     */
    protected BatchActionInterface $batchAction;

    /**
     * @var MetricsSenderLoader
     */
    protected MetricsSenderLoader $sender;

    /**
     * @var mixed[]
     */
    protected array $currentMetrics = [];

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
     * @param AMQPMessage $message
     * @param Connection  $connection
     * @param int         $channelId
     *
     * @return PromiseInterface
     */
    public function processMessage(AMQPMessage $message, Connection $connection, int $channelId): PromiseInterface
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

                    $channel->queue_declare($headers[self::REPLY_TO] ?? '', FALSE, TRUE, FALSE, FALSE);

                    return $channel;
                }
            )
            ->then(
                function (AMQPChannel $channel) use ($message, $headers): PromiseInterface {
                    try {
                        $this->logger->error($headers[self::REPLY_TO]);
                        $this->logger->error($headers[self::TYPE]);
                        switch ($headers[self::TYPE]) {
                            case 'test':
                                return $this->testAction($channel, $message, $headers);
                            case 'batch':
                                return $this->batchAction($message, $channel);
                            default:
                                return new RejectedPromise(
                                    new InvalidArgumentException(
                                        sprintf('Unsupported type "%s".', $headers[self::TYPE])
                                    )
                                );
                        }
                    } catch (OnRepeatException $e) {
                        $h = Message::getHeaders($message);
                        if (!$this->hasRepeaterHeaders($h)) {
                            [$interval, $hops] = $this->getRepeaterStuff($e);

                            $h = $this->setHopHeaders($h, $interval, $hops);
                        }

                        $h = $this->setNextHop($h);

                        $h[self::REPLY_TO] ??= $h[self::REPLY_TO];

                        $message->set(Message::APPLICATION_HEADERS, new AMQPTable($h));
                        $this->batchCallback($channel, $message, self::BATCH_REPEAT_TYPE)->wait();

                        return $this->createPromise();
                    }
                }
            )->otherwise(
                function (Throwable $e) use (
                    &$replyChannel,
                    $connection,
                    $channelId,
                    $message,
                    $headers
                ): PromiseInterface {
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
                            function () use ($e, $headers): PromiseInterface {
                                $this->logger->error(
                                    sprintf('Batch action error: %s', $e->getMessage()),
                                    array_merge(['exception' => $e], PipesHeaders::debugInfo($headers))
                                );

                                return new RejectedPromise($e);
                            }
                        );
                }
            )->then(
                $this->alwaysCallback($message, $replyChannel, $connection, $channelId, TRUE),
                $this->alwaysCallback($message, $replyChannel, $connection, $channelId, FALSE),
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

        $promise = $this->createPromise();
        $promise->then(
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

        return $promise;
    }

    /**
     *
     */
    protected function startMetrics(): void
    {
        $this->currentMetrics = CurlMetricUtils::getCurrentMetrics();
    }

    /**
     * @param AMQPMessage $message
     *
     * @return PromiseInterface
     */
    protected function validate(AMQPMessage $message): PromiseInterface
    {
        $headers = Message::getHeaders($message);

        if ($this->isEmpty($headers[self::REPLY_TO] ?? '')) {
            return new RejectedPromise(new InvalidArgumentException(sprintf(self::MISSING_HEADER, self::REPLY_TO)));
        }

        if ($this->isEmpty($headers[self::TYPE] ?? '')) {
            return new RejectedPromise(new InvalidArgumentException(sprintf(self::MISSING_HEADER, self::TYPE)));
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_ID, $headers))) {
            return new RejectedPromise(
                new InvalidArgumentException(
                    sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::NODE_ID))
                )
            );
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $headers))) {
            return new RejectedPromise(
                new InvalidArgumentException(
                    sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID))
                )
            );
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::CORRELATION_ID, $headers))) {
            return new RejectedPromise(
                new InvalidArgumentException(
                    sprintf(
                        self::MISSING_HEADER,
                        PipesHeaders::createKey(PipesHeaders::CORRELATION_ID)
                    )
                )
            );
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::PROCESS_ID, $headers))) {
            return new RejectedPromise(
                new InvalidArgumentException(
                    sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::PROCESS_ID))
                )
            );
        }

        if (!array_key_exists(PipesHeaders::createKey(PipesHeaders::PARENT_ID), $headers)) {
            return new RejectedPromise(
                new InvalidArgumentException(
                    sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::PARENT_ID))
                )
            );
        }

        return $this->createPromise();
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    protected function isEmpty(?string $value): bool
    {
        return $value === '' || $value === NULL;
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     * @param mixed[]     $headers
     *
     * @return PromiseInterface
     */
    protected function testAction(AMQPChannel $channel, AMQPMessage $message, array $headers): PromiseInterface
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
    protected function publishSuccessTestMessage(AMQPChannel $channel, array $headers): PromiseInterface
    {
        $headers = array_merge($headers, [PipesHeaders::createKey(PipesHeaders::RESULT_CODE) => 0]);
        $channel->basic_publish(Message::create('', $headers), '', $headers[self::REPLY_TO] ?? '');

        $promise = $this->createPromise();
        $promise->then(
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

        return $promise;
    }

    /**
     * @param AMQPMessage $message
     * @param AMQPChannel $channel
     *
     * @return PromiseInterface
     * @internal param Channel $channel
     */
    protected function batchAction(AMQPMessage $message, AMQPChannel $channel): PromiseInterface
    {
        $callback = fn(SuccessMessage $successMessage) => $this->itemCallback($channel, $message, $successMessage);

        $this->batchAction
            ->batchAction($message, $callback)
            ->then(fn() => $this->batchCallback($channel, $message))
            ->wait();

        return $this->createPromise();
    }

    /**
     * @param AMQPChannel    $channel
     * @param AMQPMessage    $message
     * @param SuccessMessage $successMessage
     *
     * @return PromiseInterface
     */
    protected function itemCallback(
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

            return $this->createPromise();
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

        $promise = $this->createPromise();
        $promise->then(
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

        return $promise;
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     * @param string      $type
     *
     * @return PromiseInterface
     */
    protected function batchCallback(
        AMQPChannel $channel,
        AMQPMessage $message,
        string $type = self::BATCH_END_TYPE
    ): PromiseInterface
    {
        $promise = $this->createPromise();
        $promise
            ->then(
                static function () use ($channel, $message, $type): array {
                    $headers       = Message::getHeaders($message);
                    $resultMessage = sprintf(
                        'Batch end for node %s.',
                        PipesHeaders::get(PipesHeaders::NODE_NAME, $headers)
                    );

                    $headers = array_merge(
                        $headers,
                        [
                            self::TYPE                                            => $type,
                            self::PERSISTENCE                                     => 2,
                            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $resultMessage,
                        ]
                    );

                    if (!($headers[PipesHeaders::createKey(PipesHeaders::RESULT_CODE)] ?? '')) {
                        $headers[PipesHeaders::createKey(PipesHeaders::RESULT_CODE)] = 0;
                    }

                    $channel->basic_publish(Message::create('', $headers), '', $headers[self::REPLY_TO] ?? '');

                    return $headers;
                }
            )
            ->then(
                function (array $headers): void {
                    $this->logger->debug(
                        'Published batch end.',
                        array_merge(
                            $this->prepareMessage('', '', $headers[self::REPLY_TO] ?? '', $headers),
                            PipesHeaders::debugInfo($headers)
                        )
                    );
                }
            );

        return $promise;
    }

    /**
     * @param AMQPChannel  $channel
     * @param AMQPMessage  $message
     * @param ErrorMessage $errorMessage
     * @param string       $type
     *
     * @return PromiseInterface
     */
    protected function batchErrorCallback(
        AMQPChannel $channel,
        AMQPMessage $message,
        ErrorMessage $errorMessage,
        string $type = self::BATCH_END_TYPE
    ): PromiseInterface
    {
        $headers = Message::getHeaders($message);
        $headers = array_merge(
            $headers,
            [
                self::TYPE                                            => $type,
                PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => $errorMessage->getCode(),
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $errorMessage->getMessage(),
                PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => $errorMessage->getDetail(),
            ]
        );

        $channel->basic_publish(Message::create('', $headers), '', $headers[self::REPLY_TO] ?? '');

        $promise = $this->createPromise();
        $promise->then(
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

        return $promise;
    }

    /**
     * @param AMQPMessage      $message
     * @param AMQPChannel|null $replyChannel
     * @param Connection       $connection
     * @param int              $channelId
     * @param bool             $resolve
     *
     * @return callable
     */
    protected function alwaysCallback(
        AMQPMessage $message,
        ?AMQPChannel &$replyChannel,
        Connection $connection,
        int $channelId,
        bool $resolve
    ): callable
    {
        return function ($data) use ($message, &$replyChannel, $connection, $channelId, $resolve) {
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

            if (!$resolve) {
                return new RejectedPromise($data);
            }

            return $data;
        };
    }

    /**
     * @param AMQPMessage $message
     * @param mixed[]     $startMetrics
     *
     * @throws Exception
     */
    protected function sendMetrics(AMQPMessage $message, array $startMetrics): void
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
