<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/2/17
 * Time: 10:11 AM
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Batch;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\RabbitMq\Consumer\AsyncCallbackInterface;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class BatchCallback
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\Batch
 */
class BatchConsumerCallback implements AsyncCallbackInterface, LoggerAwareInterface
{

    // Properties
    private const REPLY_TO       = 'reply-to';
    private const TYPE           = 'type';
    private const CORRELATION_ID = 'correlation-id';

    // Headers
    private const NODE_ID     = 'node_id';
    private const PROCESS_ID  = 'process_id';
    private const PARENT_ID   = 'parent_id';
    private const SEQUENCE_ID = 'sequence_id';
    private const RESULT_CODE = 'result_code';

    /**
     * @var BatchActionInterface
     */
    private $batchAction;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * BatchCallback constructor.
     *
     * @param BatchActionInterface $batchAction
     */
    public function __construct(BatchActionInterface $batchAction)
    {
        $this->batchAction = $batchAction;
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
        if ($this->isEmpty($message->getHeader(self::NODE_ID))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', self::NODE_ID)
            ));
        }
        if ($this->isEmpty($message->getHeader(self::CORRELATION_ID))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', self::CORRELATION_ID)
            ));
        }
        if ($this->isEmpty($message->getHeader(self::PROCESS_ID))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', self::PROCESS_ID)
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
     * @param Channel       $channel
     * @param Client        $client
     * @param LoopInterface $loop
     *
     * @return mixed
     * @throws Exception
     */
    public function processMessage(Message $message, Channel $channel, Client $client,
                                   LoopInterface $loop): PromiseInterface
    {
        return $this
            ->validate($message)
            ->then(function () use ($client) {
                return $client->channel();
            })
            ->then(function (Channel $channel) use ($message): PromiseInterface {
                return $channel
                    ->queueDeclare($message->getHeader(self::REPLY_TO))
                    ->then(function () use ($channel): Channel {
                        return $channel;
                    });
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
            })->otherwise(function (Exception $e) use ($channel, $message) {
                return $this
                    ->batchErrorCallback(
                        $channel,
                        $message,
                        new ErrorMessage(2001, 'UNKNOWN_ERROR', $e->getMessage()))
                    ->then(function () use ($e) {
                        $this->logger->error(sprintf('Batch action error: %s', $e->getMessage()), ['exception' => $e]);

                        return reject($e);
                    });
            });
    }

    /**
     * @param Message $message
     *
     * @return array
     */
    private function getHeaders(Message $message): array
    {
        return [
            self::CORRELATION_ID => $message->getHeader(self::CORRELATION_ID),
            self::NODE_ID        => $message->getHeader(self::NODE_ID),
            self::PROCESS_ID     => $message->getHeader(self::PROCESS_ID),
            self::PARENT_ID      => $message->getHeader(self::PARENT_ID),
        ];
    }

    /**
     * @param Channel $channel
     * @param Message $message
     *
     * @return bool|int|PromiseInterface
     */
    private function testAction(Channel $channel, Message $message): PromiseInterface
    {
        return $channel->publish(
            '',
            array_merge($this->getHeaders($message), [
                self::TYPE        => 'test',
                self::RESULT_CODE => 0,
            ]),
            '',
            $message->getHeader('reply-to')
        )->then(function (): void {
            $this->logger->info('Published test item.');
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
    private function itemCallback(Channel $channel, Message $message, SuccessMessage $successMessage): PromiseInterface
    {
        return $channel->publish(
            sprintf('{"data":%s,"settings":%s}', $successMessage->getData(), $successMessage->getSetting()),
            array_merge(
                $this->getHeaders($message),
                $successMessage->getHeaders(),
                [
                    self::TYPE        => 'batch_item',
                    self::SEQUENCE_ID => $successMessage->getSequenceId(),
                    self::RESULT_CODE => 0,
                ]
            ),
            '',
            $message->getHeader('reply-to')
        )
            ->then(function () use ($successMessage): void {
                $this->logger->info(sprintf('Published batch item %s.', $successMessage->getSequenceId()));
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
        return $channel->publish(
            '',
            array_merge($this->getHeaders($message), [
                self::TYPE        => 'batch_end',
                self::RESULT_CODE => 0,
            ]),
            '',
            $message->getHeader('reply-to')
        )->then(function (): void {
            $this->logger->info('Published batch total.');
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
    private function batchErrorCallback(Channel $channel, Message $message,
                                        ErrorMessage $errorMessage): PromiseInterface
    {
        return $channel->publish(
            json_encode([
                'result_code'    => $errorMessage->getCode(),
                'result_status'  => $errorMessage->getStatus(),
                'result_message' => $errorMessage->getMessage(),
                'result_detail'  => $errorMessage->getDetail(),
            ]),
            array_merge($this->getHeaders($message), [
                self::TYPE        => 'batch_end',
                self::RESULT_CODE => $errorMessage->getCode(),
            ]),
            '',
            $message->getHeader('reply-to')
        )->then(function (): void {
            $this->logger->info('Published batch error total.');
        });
    }

}