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
class BatchCallback implements AsyncCallbackInterface, LoggerAwareInterface
{

    // Properties
    private const REPLY_TO       = 'reply-to';
    private const TYPE           = 'type';
    private const CORRELATION_ID = 'correlation-id';

    // Headers
    private const NODE_ID = 'node_id';

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
    protected function validate(Message $message): PromiseInterface
    {
        if ($this->isEmpty($message->getHeader(self::REPLY_TO))) {
            return reject(new Exception(sprintf('Missing "%s" in the message header.', self::REPLY_TO)));
        }
        if ($this->isEmpty($message->getHeader(self::TYPE))) {
            return reject(new Exception(sprintf('Missing "%s" in the message header.', self::TYPE)));
        }
        if ($this->isEmpty($message->getHeader(self::NODE_ID))) {
            return reject(new Exception(sprintf('Missing "%s" in the message header.', self::NODE_ID)));
        }
        if ($this->isEmpty($message->getHeader(self::CORRELATION_ID))) {
            return reject(new Exception(sprintf('Missing "%s" in the message header.', self::CORRELATION_ID)));
        }

        return resolve();
    }

    /**
     * @param null|string $value
     *
     * @return bool
     */
    protected function isEmpty(?string $value): bool
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
                        return reject(new Exception());
                }
            });
    }

    /**
     * @param Message $message
     *
     * @return array
     */
    public function getHeaders(Message $message): array
    {
        return [
            'correlation-id' => $message->getHeader(self::CORRELATION_ID),
            'node_id'        => $message->getHeader(self::NODE_ID),
        ];
    }

    /**
     * @param Channel $channel
     * @param Message $message
     *
     * @return bool|int|PromiseInterface
     */
    public function testAction(Channel $channel, Message $message): PromiseInterface
    {
        return $channel->publish(
            '',
            array_merge($this->getHeaders($message), [
                'type' => 'test',
            ]),
            '',
            $message->getHeader('reply-to')
        );
    }

    /**
     * @param Message       $message
     * @param Channel       $channel
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     * @internal param Channel $channel
     */
    public function batchAction(Message $message, Channel $channel, LoopInterface $loop): PromiseInterface
    {
        $itemCallBack = function (array $data) use ($message, $channel) {
            return $this->itemCallback($channel, $message, $data);
        };

        return $this->batchAction
            ->batchAction($message, $loop, $itemCallBack)
            ->then(function () use ($channel, $message) {
                return $this->batchCallback($channel, $message);
            });
    }

    /**
     * @param Channel $channel
     * @param Message $message
     * @param array   $data
     *
     * @return PromiseInterface
     */
    private function itemCallback(Channel $channel, Message $message, array $data): PromiseInterface
    {
        return $channel->publish(
            json_encode($data),
            array_merge($this->getHeaders($message), [
                'type' => 'batch_item',
            ]),
            '',
            $message->getHeader('reply-to')
        )
            ->then(function () use ($data): void {
                $this->logger->info(sprintf('Published batch item %s.', $data['id']));
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
                'type' => 'batch_total',
            ]),
            '',
            $message->getHeader('reply-to')
        )->then(function (): void {
            $this->logger->info('Published batch total');
        });
    }

}