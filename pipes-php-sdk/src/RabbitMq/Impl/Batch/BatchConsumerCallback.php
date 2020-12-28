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
    protected const  BATCH_ITEM        = 'batch_item';

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
     * @throws Exception
     */
    public function processMessage(AMQPMessage $message, Connection $connection, int $channelId): PromiseInterface
    {
        $this->startMetrics();
        $channel = $connection->getChannel($channelId);

        return $this->validate($message)->then(
            function () use ($channel, $message): PromiseInterface {
                $headers = Message::getHeaders($message);
                $channel->queue_declare($headers[self::REPLY_TO] ?? '', FALSE, TRUE, FALSE, FALSE);

                try {
                    switch ($headers[self::TYPE]) {
                        case 'test':
                            return $this->testAction($channel, $message);
                        case 'batch':
                            return $this->batchAction($channel, $message);
                        default:
                            return new RejectedPromise(
                                new InvalidArgumentException(
                                    sprintf('Unsupported type "%s".', $headers[self::TYPE])
                                )
                            );
                    }
                } catch (OnRepeatException $e) {
                    if (!$this->hasRepeaterHeaders($headers)) {
                        [$interval, $hops] = $this->getRepeaterStuff($e);

                        $headers = $this->setHopHeaders($headers, $interval, $hops);
                    }

                    $message->set(Message::APPLICATION_HEADERS, new AMQPTable($headers));
                    $message = $this->setNextHop($message);
                    $this->batchCallback($channel, $message, self::BATCH_REPEAT_TYPE)->wait();

                    return $this->createPromise();
                }
            }
        )->otherwise(
            function (Throwable $e) use ($channel, $message): PromiseInterface {
                $this->publish($channel, $message, 2_001, $e->getMessage(), $e->getTraceAsString());

                return $this->createPromise()->then(
                    function () use ($e, $message): PromiseInterface {
                        $headers = Message::getHeaders($message);
                        $this->logger->error(
                            sprintf('Batch action error: %s', $e->getMessage()),
                            array_merge(['exception' => $e], PipesHeaders::debugInfo($headers))
                        );

                        return new RejectedPromise($e);
                    }
                );
            }
        )->then(
            $this->alwaysCallback($message, $connection, $channelId, TRUE),
            $this->alwaysCallback($message, $connection, $channelId, FALSE),
        );
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

        $this->logger->debug(
            'Batch consumer received message',
            array_merge(
                $this->prepareBunnyMessage($message),
                PipesHeaders::debugInfo($headers)
            )
        );

        return $this->createPromise();
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     *
     * @return PromiseInterface
     */
    protected function testAction(AMQPChannel $channel, AMQPMessage $message): PromiseInterface
    {
        try {
            /** @var string $nodeName */
            $nodeName = PipesHeaders::get(PipesHeaders::NODE_NAME, Message::getHeaders($message));
            $this->batchAction->getBatchService($nodeName);

            return $this->publishSuccessTestMessage($channel, $message);
        } catch (Exception $e) {
            return $this->publishErrorTestMessage($channel, $message, $e);
        }
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     *
     * @return PromiseInterface
     */
    protected function publishSuccessTestMessage(AMQPChannel $channel, AMQPMessage $message): PromiseInterface
    {
        $this->publish($channel, $message, 0);

        return $this->createPromise()->then(
            function () use ($message): void {
                $headers = Message::getHeaders($message);
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
    protected function publishErrorTestMessage(
        AMQPChannel $channel,
        AMQPMessage $message,
        Exception $e
    ): PromiseInterface
    {
        $this->publish($channel, $message, 2_001, $e->getMessage(), $e->getTraceAsString());

        return $this->createPromise()->then(
            function () use ($message): void {
                $headers = Message::getHeaders($message);
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
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     *
     * @return PromiseInterface
     */
    protected function batchAction(AMQPChannel $channel, AMQPMessage $message): PromiseInterface
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
        $headers       = array_merge(Message::getHeaders($message), $successMessage->getHeaders());
        $resultMessage = sprintf(
            'Batch item %s for node %s.',
            $successMessage->getSequenceId(),
            PipesHeaders::get(PipesHeaders::NODE_NAME, $headers)
        );

        $this->publish(
            $channel,
            Message::create($successMessage->getData(), $headers),
            0,
            $resultMessage,
            '',
            self::BATCH_ITEM,
            $successMessage->getSequenceId()
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
                function () use ($channel, $message, $type): void {
                    $headers       = Message::getHeaders($message);
                    $resultMessage = sprintf(
                        'Batch end for node %s.',
                        PipesHeaders::get(PipesHeaders::NODE_NAME, $headers)
                    );

                    $resultCode = $headers[PipesHeaders::createKey(PipesHeaders::RESULT_CODE)] ?? '';
                    if (!$resultCode) {
                        $resultCode = 0;
                    }

                    $this->publish($channel, $message, $resultCode, $resultMessage, '', $type);
                }
            )
            ->then(
                function () use ($message): void {
                    $headers = Message::getHeaders($message);
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
     * @param AMQPMessage $message
     * @param Connection  $connection
     * @param int         $channelId
     * @param bool        $resolve
     *
     * @return callable
     */
    protected function alwaysCallback(
        AMQPMessage $message,
        Connection $connection,
        int $channelId,
        bool $resolve
    ): callable
    {
        return function ($data) use ($message, $connection, $channelId, $resolve) {
            Message::ack($message, $connection, $channelId);
            $this->sendMetrics($message, $this->currentMetrics);

            if (!$resolve) {
                return new RejectedPromise($data);
            }

            return $data;
        };
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     * @param int         $resultCode
     * @param string      $resultMessage
     * @param string      $resultDetail
     * @param string|null $type
     * @param int|null    $sequenceId
     */
    protected function publish(
        AMQPChannel $channel,
        AMQPMessage $message,
        int $resultCode,
        string $resultMessage = '',
        string $resultDetail = '',
        ?string $type = NULL,
        ?int $sequenceId = NULL
    ): void
    {
        $body    = Message::getBody($message);
        $headers = Message::getHeaders($message);
        $headers = array_merge(
            $headers,
            [
                self::PERSISTENCE                                     => 2,
                PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => $resultCode,
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $resultMessage,
                PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => $resultDetail,
                PipesHeaders::createKey(PipesHeaders::TIMESTAMP)      => (string) round(microtime(TRUE) * 1_000),
            ]
        );

        if ($type) {
            $headers = array_merge($headers, [self::TYPE => $type]);
        }

        if ($sequenceId) {
            $headers = array_merge($headers, [PipesHeaders::createKey(PipesHeaders::SEQUENCE_ID) => $sequenceId]);
        }

        $channel->basic_publish(Message::create($body, $headers), '', $headers[self::REPLY_TO] ?? '');
    }

    /**
     *
     */
    protected function startMetrics(): void
    {
        $this->currentMetrics = CurlMetricUtils::getCurrentMetrics();
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
     * @param AMQPMessage $message
     * @param mixed[]     $startMetrics
     *
     * @throws Exception
     */
    protected function sendMetrics(AMQPMessage $message, array $startMetrics): void
    {
        $headers = Message::getHeaders($message);
        $times   = CurlMetricUtils::getTimes($startMetrics);
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
