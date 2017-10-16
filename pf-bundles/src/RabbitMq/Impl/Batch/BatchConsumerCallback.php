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
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DebugMessageTrait;
use Hanaboso\PipesFramework\RabbitMq\Consumer\AsyncCallbackInterface;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Throwable;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class BatchCallback
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\Batch
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
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_ID, $message->headers))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::NODE_ID)
            ));
        }
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::CORRELATION_ID, $message->headers))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::CORRELATION_ID)
            ));
        }
        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::PROCESS_ID, $message->headers))) {
            return reject(new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::PROCESS_ID)
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
            })->otherwise(function (Throwable $e) use ($channel, $message) {
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
     * @param Channel $channel
     * @param Message $message
     *
     * @return bool|int|PromiseInterface
     */
    private function testAction(Channel $channel, Message $message): PromiseInterface
    {
        $headers = array_merge($message->headers, [
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE) => 0,
        ]);

        return $channel->publish('', $headers, '', $message->getHeader('reply-to')
        )->then(function () use ($message, $headers): void {
            $this->logger->info(
                'Published test item.',
                $this->prepareMessage('', '', $message->getHeader('reply-to'), $headers)
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
    private function itemCallback(Channel $channel, Message $message, SuccessMessage $successMessage): PromiseInterface
    {
        $headers = array_merge(
            $message->headers,
            $successMessage->getHeaders(),
            [
                self::TYPE                                         => 'batch_item',
                PipesHeaders::createKey(PipesHeaders::SEQUENCE_ID) => $successMessage->getSequenceId(),
                PipesHeaders::createKey(PipesHeaders::RESULT_CODE) => 0,
            ]
        );

        return $channel->publish(
            sprintf('{"data":%s,"settings":%s}', $successMessage->getData(), $successMessage->getSetting()),
            $headers,
            '',
            $message->getHeader('reply-to')
        )
            ->then(function () use ($successMessage, $message, $headers): void {
                $this->logger->info(
                    sprintf('Published batch item %s.', $successMessage->getSequenceId()),
                    $this->prepareMessage('', '', $message->getHeader('reply-to'), $headers)
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
        $headers = array_merge($message->headers, [
            self::TYPE                                         => 'batch_end',
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE) => 0,
        ]);

        return $channel
            ->publish('', $headers, '', $message->getHeader('reply-to')
            )->then(function () use ($message, $headers): void {
                $this->logger->info(
                    'Published batch end.',
                    $this->prepareMessage('', '', $message->getHeader('reply-to'), $headers)
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
    private function batchErrorCallback(Channel $channel, Message $message,
                                        ErrorMessage $errorMessage): PromiseInterface
    {
        $headers = array_merge($message->headers, [
            self::TYPE                                            => 'batch_end',
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => $errorMessage->getCode(),
            PipesHeaders::createKey(PipesHeaders::RESULT_STATUS)  => $errorMessage->getStatus(),
            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $errorMessage->getMessage(),
            PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => $errorMessage->getDetail(),
        ]);

        return $channel
            ->publish('', $headers, '', $message->getHeader('reply-to'))
            ->then(function () use ($message, $headers): void {
                $this->logger->info(
                    'Published batch error end.',
                    $this->prepareMessage('', '', $message->getHeader('reply-to'), $headers)
                );
            });
    }

}