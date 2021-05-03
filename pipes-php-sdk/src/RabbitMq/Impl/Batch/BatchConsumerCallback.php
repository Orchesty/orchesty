<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

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
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;
use RabbitMqBundle\Consumer\DebugMessageTrait;
use RabbitMqBundle\Utils\Message;
use Throwable;

/**
 * Class BatchConsumerCallback
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
class BatchConsumerCallback implements CallbackInterface, LoggerAwareInterface
{

    use DebugMessageTrait;
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
     * BatchConsumerCallback constructor.
     *
     * @param BatchActionInterface $batchAction
     * @param MetricsSenderLoader  $sender
     */
    public function __construct(protected BatchActionInterface $batchAction, protected MetricsSenderLoader $sender)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param AMQPMessage $message
     * @param Connection  $connection
     * @param int         $channelId
     *
     * @throws Throwable
     */
    public function processMessage(AMQPMessage $message, Connection $connection, int $channelId): void
    {
        $startMetrics = CurlMetricUtils::getCurrentMetrics();
        $channel      = $connection->getChannel($channelId);
        try {
            $this->log($message, 'Batch consumer received message');
            $this->validate($message);
            $headers = Message::getHeaders($message);
            $channel->queue_declare($headers[self::REPLY_TO] ?? '', FALSE, TRUE, FALSE, FALSE);
            switch ($headers[self::TYPE]) {
                case 'test':
                    $this->testAction($channel, $message);

                    break;
                case 'batch':
                    $this->batchAction($channel, $message);

                    break;
                default:
                    throw new InvalidArgumentException(sprintf('Unsupported type "%s".', $headers[self::TYPE]));
            }
        } catch (OnRepeatException $e) {
            $this->repeatCallback($channel, $message, $e);
        } catch (InvalidArgumentException $t) {
            $this->publish($channel, $message, 2_001, $t->getMessage(), $t->getTraceAsString(), self::BATCH_END_TYPE);
            $this->log($message, sprintf('Batch action error: %s', $t->getMessage()), LogLevel::ERROR);
        } catch (Throwable $t) {
            $this->log($message, sprintf('Batch action error: %s', $t->getMessage()), LogLevel::CRITICAL);

            throw $t;
        } finally {
            Message::ack($message, $connection, $channelId);
            $this->sendMetrics($message, $startMetrics);
        }
    }

    /**
     * @param AMQPMessage $message
     */
    protected function validate(AMQPMessage $message): void
    {
        $headers = Message::getHeaders($message);

        if ($this->isEmpty($headers[self::REPLY_TO] ?? '')) {
            throw new InvalidArgumentException(sprintf(self::MISSING_HEADER, self::REPLY_TO));
        }

        if ($this->isEmpty($headers[self::TYPE] ?? '')) {
            throw new InvalidArgumentException(sprintf(self::MISSING_HEADER, self::TYPE));
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_ID, $headers))) {
            throw new InvalidArgumentException(
                sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::NODE_ID)),
            );
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $headers))) {
            throw new InvalidArgumentException(
                sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)),
            );
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::CORRELATION_ID, $headers))) {
            throw new InvalidArgumentException(
                sprintf(
                    self::MISSING_HEADER,
                    PipesHeaders::createKey(PipesHeaders::CORRELATION_ID),
                ),
            );
        }

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::PROCESS_ID, $headers))) {
            throw new InvalidArgumentException(
                sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::PROCESS_ID)),
            );
        }

        if (!array_key_exists(PipesHeaders::createKey(PipesHeaders::PARENT_ID), $headers)) {
            throw new InvalidArgumentException(
                sprintf(self::MISSING_HEADER, PipesHeaders::createKey(PipesHeaders::PARENT_ID)),
            );
        }
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     */
    protected function testAction(AMQPChannel $channel, AMQPMessage $message): void
    {
        try {
            /** @var string $nodeName */
            $nodeName = PipesHeaders::get(PipesHeaders::NODE_NAME, Message::getHeaders($message));
            $this->batchAction->getBatchService($nodeName);

            $this->publishSuccessTestMessage($channel, $message);
        } catch (Throwable $t) {
            $this->publishErrorTestMessage($channel, $message, $t);
        }
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     */
    protected function publishSuccessTestMessage(AMQPChannel $channel, AMQPMessage $message): void
    {
        $this->publish($channel, $message, 0);
        $this->log($message, 'Published test item.');
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     * @param Throwable   $t
     */
    protected function publishErrorTestMessage(AMQPChannel $channel, AMQPMessage $message, Throwable $t): void
    {
        $this->publish($channel, $message, 2_001, $t->getMessage(), $t->getTraceAsString());
        $this->log($message, 'Published test item error.');
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     */
    protected function batchAction(AMQPChannel $channel, AMQPMessage $message): void
    {
        $this->batchAction->batchAction(
            $message,
            function (SuccessMessage $successMessage) use ($channel, $message): void {
                $this->itemCallback($channel, $message, $successMessage);
            },
        );
        $this->batchCallback($channel, $message);
    }

    /**
     * @param AMQPChannel    $channel
     * @param AMQPMessage    $message
     * @param SuccessMessage $successMessage
     */
    protected function itemCallback(AMQPChannel $channel, AMQPMessage $message, SuccessMessage $successMessage): void
    {
        $headers       = array_merge(Message::getHeaders($message), $successMessage->getHeaders());
        $resultMessage = sprintf(
            'Batch item %s for node %s.',
            $successMessage->getSequenceId(),
            PipesHeaders::get(PipesHeaders::NODE_NAME, $headers),
        );

        $this->publish(
            $channel,
            Message::create($successMessage->getData(), $headers),
            0,
            $resultMessage,
            '',
            self::BATCH_ITEM,
            $successMessage->getSequenceId(),
        );

        $this->log($message, sprintf('Published batch item %s.', $successMessage->getSequenceId()));
    }

    /**
     * @param AMQPChannel $channel
     * @param AMQPMessage $message
     * @param string      $type
     */
    protected function batchCallback(
        AMQPChannel $channel,
        AMQPMessage $message,
        string $type = self::BATCH_END_TYPE,
    ): void
    {
        $headers       = Message::getHeaders($message);
        $resultMessage = sprintf(
            'Batch end for node %s.',
            PipesHeaders::get(PipesHeaders::NODE_NAME, $headers),
        );

        $resultCode = PipesHeaders::get(PipesHeaders::RESULT_CODE, $headers) ?? 0;
        $this->publish($channel, $message, intval($resultCode), $resultMessage, '', $type);
        $this->log($message, $resultMessage);
    }

    /**
     * @param AMQPChannel       $channel
     * @param AMQPMessage       $message
     * @param OnRepeatException $e
     */
    protected function repeatCallback(AMQPChannel $channel, AMQPMessage $message, OnRepeatException $e): void
    {
        try {
            $headers = Message::getHeaders($message);
            if (!$this->hasRepeaterHeaders($headers)) {
                $headers = $this->setHopHeaders($headers, ...$this->getRepeaterStuff($e));
            }

            $message->set(Message::APPLICATION_HEADERS, new AMQPTable($headers));
            $message = $this->setNextHop($message);
        } catch (Throwable $t) {
            $this->log($message, sprintf('Set repeat headers failed: "%s', $t->getMessage()), LogLevel::ERROR, [], $t);
        }
        $this->batchCallback($channel, $message, self::BATCH_REPEAT_TYPE);
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
        ?int $sequenceId = NULL,
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
            ],
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
     */
    protected function sendMetrics(AMQPMessage $message, array $startMetrics): void
    {
        try {
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
                    MetricsEnum::TOPOLOGY_ID    => PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $headers),
                    MetricsEnum::CORRELATION_ID => PipesHeaders::get(PipesHeaders::CORRELATION_ID, $headers),
                    MetricsEnum::NODE_ID        => PipesHeaders::get(PipesHeaders::NODE_ID, $headers),
                ],
            );
        } catch (Throwable $t) {
            $this->log($message, sprintf('Send metrics failed: "%s"', $t->getMessage()), LogLevel::ERROR, [], $t);
        }
    }

    /**
     * @param AMQPMessage    $message
     * @param string         $text
     * @param string         $level
     * @param mixed[]        $context
     * @param Throwable|null $t
     */
    protected function log(
        AMQPMessage $message,
        string $text,
        string $level = LogLevel::DEBUG,
        array $context = [],
        ?Throwable $t = NULL,
    ): void
    {
        $this->logger->log(
            $level,
            $text,
            array_merge(
                PipesHeaders::debugInfo(Message::getHeaders($message)),
                ['Exception' => $t, 'Message' => $message],
                $context,
            ),
        );
    }

}
