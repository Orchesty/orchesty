<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use Bunny\Channel;
use Bunny\Message;
use Exception;
use Hanaboso\CommonsBundle\Enum\MetricsEnum;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Metrics\Impl\InfluxDbSender;
use Hanaboso\CommonsBundle\Utils\CurlMetricUtils;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\AsyncCallbackInterface;
use RabbitMqBundle\Consumer\DebugMessageTrait;
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
    private const REPLY_TO = 'reply-to';
    private const TYPE     = 'type';

    /**
     * @var BatchActionInterface
     */
    private $batchAction;

    /**
     * @var InfluxDbSender
     */
    private $sender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $currentMetrics = [];

    /**
     * BatchConsumerCallback constructor.
     *
     * @param BatchActionInterface $batchAction
     * @param InfluxDbSender       $sender
     */
    public function __construct(BatchActionInterface $batchAction, InfluxDbSender $sender)
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
     * @param Message $message
     *
     * @return PromiseInterface
     */
    private function validate(Message $message): PromiseInterface
    {
        if ($this->isEmpty($message->getHeader(self::REPLY_TO))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', self::REPLY_TO)
            ));
        }
        if ($this->isEmpty($message->getHeader(self::TYPE))) {
            return reject(new InvalidArgumentException(
                    sprintf('Missing "%s" in the message header.', self::TYPE)
                )
            );
        }
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_ID, $message->headers))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::NODE_ID))
            ));
        }
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $message->headers))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID))
            ));
        }
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::CORRELATION_ID, $message->headers))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::CORRELATION_ID))
            ));
        }
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::PROCESS_ID, $message->headers))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::PROCESS_ID))
            ));
        }
        if (!array_key_exists(PipesHeaders::createKey(PipesHeaders::PARENT_ID), $message->headers)) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::createKey(PipesHeaders::PARENT_ID))
            ));
        }

        return resolve();
    }

    /**
     * @param null|string $value
     *
     * @return bool
     */
    private function isEmpty(?string $value): bool
    {
        return $value === '' || $value === NULL;
    }

    /**
     * @param Message       $message
     * @param Connection    $connection
     * @param int           $channelId
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     */
    public function processMessage(
        Message $message,
        Connection $connection,
        int $channelId,
        LoopInterface $loop): PromiseInterface
    {
        $this->startMetrics();

        // @todo use class property - array of channels ?
        /** @var Channel|null $replyChannel */
        $replyChannel = NULL;

        return $this
            ->validate($message)
            ->then(function () use ($message): void {
                $this->logger->debug(
                    'Batch consumer received message',
                    array_merge(
                        $this->prepareBunnyMessage($message),
                        PipesHeaders::debugInfo($message->headers)
                    ));
            })
            ->then(function () use ($connection): Channel {
                /** @var Channel $channel */
                $channel = $connection->getClient()->channel();

                return $channel;
            })
            ->then(function (Channel $channel) use ($message, &$replyChannel) {
                $replyChannel = $channel;

                $channel->queueDeclare($message->getHeader(self::REPLY_TO));

                return $channel;
            })
            ->then(function (Channel $channel) use ($message, $loop) {
                switch ($message->getHeader(self::TYPE)) {
                    case 'test':
                        return $this->testAction($channel, $message);
                        break;
                    case 'batch':
                        return $this->batchAction($message, $channel, $loop);
                        break;
                    default:
                        return reject(
                            new InvalidArgumentException(sprintf(
                                'Unsupported type "%s".',
                                $message->getHeader(self::TYPE)
                            ))
                        );
                }
            })->otherwise(function (Throwable $e) use ($replyChannel, $connection, $channelId, $message) {
                if ($replyChannel === NULL) {
                    // @todo create new channel
                    $replyChannel = $connection->getChannel($channelId);
                }

                return $this
                    ->batchErrorCallback(
                        $replyChannel,
                        $message,
                        new ErrorMessage(2001, $e->getMessage()))
                    ->then(function () use ($e, $message) {
                        $this->logger->error(sprintf('Batch action error: %s', $e->getMessage()), array_merge(
                            ['exception' => $e],
                            PipesHeaders::debugInfo($message->headers)
                        ));

                        return reject($e);
                    });
            })->always(function () use ($message, &$replyChannel, $connection, $channelId): void {
                /** @var Channel|null $channel */
                $channel      = $replyChannel;
                $replyChannel = $channel;
                $connection->getChannel($channelId)->ack($message);

                if ($replyChannel !== NULL) {
                    $replyChannel->close();
                    unset($replyChannel);
                    unset($channel);
                }

                $this->sendMetrics($message, $this->currentMetrics);
            });
    }

    /**
     * @param Channel $channel
     * @param Message $message
     *
     * @return PromiseInterface
     */
    private function testAction(Channel $channel, Message $message): PromiseInterface
    {
        try {
            /** @var string $nodeName */
            $nodeName = PipesHeaders::get(PipesHeaders::NODE_NAME, $message->headers);
            $this->batchAction->getBatchService($nodeName);

            return $this->publishSuccessTestMessage($channel, $message);
        } catch (Exception $e) {
            return $this->publishErrorTestMessage($channel, $message, $e);
        }
    }

    /**
     * @param Channel $channel
     * @param Message $message
     *
     * @return PromiseInterface
     */
    private function publishSuccessTestMessage(Channel $channel, Message $message): PromiseInterface
    {
        $headers = array_merge($message->headers, [
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE) => 0,
        ]);

        $channel->publish('', $headers, '', $message->getHeader(self::REPLY_TO));

        return (new Promise(function (callable $resolve): void {
            $resolve();
        }))->then(function () use ($message, $headers): void {
            $this->logger->debug(
                'Published test item.',
                array_merge(
                    $this->prepareMessage('', '', $message->getHeader(self::REPLY_TO), $headers),
                    PipesHeaders::debugInfo($headers)
                )
            );
        });
    }

    /**
     * @param Channel   $channel
     * @param Message   $message
     * @param Exception $e
     *
     * @return PromiseInterface
     */
    public function publishErrorTestMessage(Channel $channel, Message $message, Exception $e): PromiseInterface
    {
        $headers = array_merge($message->headers, [
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => 2001,
            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $e->getMessage(),
        ]);

        $channel->publish('', $headers, '', $message->getHeader(self::REPLY_TO));

        return (new Promise(function (callable $resolve): void {
            $resolve();
        }))->then(function () use ($message, $headers): void {
            $this->logger->error(
                'Published test item error.',
                array_merge(
                    $this->prepareMessage('', '', $message->getHeader(self::REPLY_TO), $headers),
                    PipesHeaders::debugInfo($headers)
                )
            );
        });
    }

    /**
     * @param Message       $message
     * @param Channel       $channel
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     * @internal param Channel $channel
     */
    private function batchAction(Message $message, Channel $channel, LoopInterface $loop): PromiseInterface
    {
        $itemCallBack = function (SuccessMessage $successMessage) use ($message, $channel) {
            return $this->itemCallback($channel, $message, $successMessage);
        };

        return $this->batchAction
            ->batchAction($message, $loop, $itemCallBack)
            ->then(function () use ($channel, $message) {
                return $this->batchCallback($channel, $message);
            });
    }

    /**
     * @param Channel        $channel
     * @param Message        $message
     * @param SuccessMessage $successMessage
     *
     * @return PromiseInterface
     */
    private function itemCallback(Channel $channel,
                                  Message $message,
                                  SuccessMessage $successMessage): PromiseInterface
    {
        // Limiter
        if (
            $successMessage->hasHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE)) &&
            $successMessage->getHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE)) == 1004
        ) {
            $message->headers[PipesHeaders::createKey(PipesHeaders::RESULT_CODE)] = 1004;

            return resolve();
        }

        $resultMessage = sprintf(
            'Batch item %s for node %s.',
            $successMessage->getSequenceId(),
            PipesHeaders::get(PipesHeaders::NODE_NAME, $message->headers)
        );
        $headers       = array_merge(
            $message->headers,
            $successMessage->getHeaders(),
            [
                self::TYPE                                            => 'batch_item',
                PipesHeaders::createKey(PipesHeaders::SEQUENCE_ID)    => $successMessage->getSequenceId(),
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $resultMessage,
            ]
        );

        $headers[PipesHeaders::createKey(PipesHeaders::TIMESTAMP)] = (string) round(microtime(TRUE) * 1000);
        $channel->publish($successMessage->getData(), $headers, '', $message->getHeader(self::REPLY_TO));

        return (new Promise(function (callable $resolve): void {
            $resolve();
        }))->then(function () use ($successMessage, $message, $headers): void {
            $this->logger->debug(
                sprintf('Published batch item %s.', $successMessage->getSequenceId()),
                array_merge(
                    $this->prepareMessage('', '', $message->getHeader(self::REPLY_TO), $headers),
                    PipesHeaders::debugInfo($headers)
                )
            );
        });
    }

    /**
     * @param Channel $channel
     * @param Message $message
     *
     * @return PromiseInterface
     * @throws Exception
     */
    private function batchCallback(Channel $channel, Message $message): PromiseInterface
    {
        $resultMessage = sprintf(
            'Batch end for node %s.',
            PipesHeaders::get(PipesHeaders::NODE_NAME, $message->headers)
        );

        $headers = array_merge($message->headers, [
            self::TYPE                                            => 'batch_end',
            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $resultMessage,
        ]);

        if (!$message->hasHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE))) {
            $headers[PipesHeaders::createKey(PipesHeaders::RESULT_CODE)] = 0;
        }

        $channel->publish('', $headers, '', $message->getHeader(self::REPLY_TO));

        return (new Promise(function (callable $resolve): void {
            $resolve();
        }))->then(function () use ($message, $headers): void {
            $this->logger->debug(
                'Published batch end.',
                array_merge(
                    $this->prepareMessage('', '', $message->getHeader(self::REPLY_TO), $headers),
                    PipesHeaders::debugInfo($headers)
                )
            );
        });
    }

    /**
     * @param Channel      $channel
     * @param Message      $message
     *
     * @param ErrorMessage $errorMessage
     *
     * @return PromiseInterface
     */
    private function batchErrorCallback(
        Channel $channel,
        Message $message,
        ErrorMessage $errorMessage): PromiseInterface
    {
        $headers = array_merge($message->headers, [
            self::TYPE                                            => 'batch_end',
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => $errorMessage->getCode(),
            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $errorMessage->getMessage(),
            PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => $errorMessage->getDetail(),
        ]);

        $channel->publish('', $headers, '', $message->getHeader(self::REPLY_TO));

        return (new Promise(function (callable $resolve): void {
            $resolve();
        }))->then(function () use ($message, $headers): void {
            $this->logger->error(
                'Published batch error end.',
                array_merge(
                    $this->prepareMessage('', '', $message->getHeader(self::REPLY_TO), $headers),
                    PipesHeaders::debugInfo($headers)
                )
            );
        });
    }

    /**
     * @param Message $message
     * @param array   $startMetrics
     *
     * @throws DateTimeException
     */
    private function sendMetrics(Message $message, array $startMetrics): void
    {
        $times = CurlMetricUtils::getTimes($startMetrics);
        $this->sender->send(
            [
                MetricsEnum::REQUEST_TOTAL_DURATION => $times[CurlMetricUtils::KEY_REQUEST_DURATION],
                MetricsEnum::CPU_USER_TIME          => $times[CurlMetricUtils::KEY_USER_TIME],
                MetricsEnum::CPU_KERNEL_TIME        => $times[CurlMetricUtils::KEY_KERNEL_TIME],
            ],
            [
                MetricsEnum::HOST           => gethostname(),
                MetricsEnum::TOPOLOGY_ID    => $message->getHeader(PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)),
                MetricsEnum::CORRELATION_ID => $message->getHeader(PipesHeaders::createKey(PipesHeaders::CORRELATION_ID)),
                MetricsEnum::NODE_ID        => $message->getHeader(PipesHeaders::createKey(PipesHeaders::NODE_ID)),
            ]
        );
    }

}
