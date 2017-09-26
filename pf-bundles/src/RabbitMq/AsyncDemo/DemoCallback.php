<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.9.17
 * Time: 16:46
 */

namespace Hanaboso\PipesFramework\RabbitMq\AsyncDemo;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Clue\React\Buzz\Browser;
use Exception;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\RabbitMq\Base\AsyncCallbackInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function React\Promise\all;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class DemoCallback
 *
 * @package Hanaboso\PipesFramework\Commons\RabbitMq\AsyncDemo
 */
class DemoCallback implements AsyncCallbackInterface
{

    // Properties
    private const REPLY_TO       = 'reply-to';
    private const TYPE           = 'type';
    private const CORRELATION_ID = 'correlation-id';

    // Headers
    private const NODE_ID = 'node_id';

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
        $browser = new Browser($loop);

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
            ->then(function (Channel $channel) use ($message, $browser) {
                switch ($message->getHeader(self::TYPE)) {
                    case 'test':
                        return $this->testAction($channel, $message);
                        break;
                    case 'batch':
                        return $this->batchAction($channel, $message, $browser);
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
                echo 'Bath item ' . $data['id'] . ' published' . PHP_EOL;
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
            echo 'Bath total published' . PHP_EOL;
        });
    }

    // Connector example

    /**
     * @param Channel $channel
     * @param Message $message
     * @param Browser $browser
     *
     * @return PromiseInterface
     */
    public function batchAction(Channel $channel, Message $message, Browser $browser): PromiseInterface
    {
        return all($this->getRequests($browser, $message, $channel))
            ->then(function () use ($channel): Channel {
                return $channel;
            })->then(function (Channel $channel) use ($message): PromiseInterface {
                return $this->batchCallback($channel, $message);
            });
    }

    /**
     * @param Browser $browser
     * @param Message $message
     * @param Channel $channel
     *
     * @return array
     */
    private function getRequests(Browser $browser, Message $message, Channel $channel): array
    {
        $requests = [];
        for ($i = 1; $i <= 10; $i++) {
            $requests[] = $this
                ->fetchData($browser, $this->createRequest($i))
                ->then(function (ResponseInterface $response) use ($i): array {
                    if ($response->getHeader('content-type') == 'application/json') {
                        return [
                            'id'   => $i,
                            'data' => json_decode($response->getBody()->getContents()),
                        ];
                    } else {
                        return [
                            'id'   => $i,
                            'data' => json_encode($response->getBody()->getContents()),
                        ];
                    }
                    // @todo add reject function
                })->then(function (array $data) use ($channel, $message): PromiseInterface {
                    return $this->itemCallback($channel, $message, $data);
                });
        }

        return $requests;
    }

    /**
     * @param int $page
     *
     * @return RequestInterface
     */
    private function createRequest(int $page): RequestInterface
    {
        return new Request('GET', 'http://jsonplaceholder.typicode.com/posts/' . $page);
    }

    /**
     * @param Browser          $browser
     * @param RequestInterface $request
     *
     * @return Promise
     */
    protected function fetchData(Browser $browser, RequestInterface $request): Promise
    {
        return $browser->send($request);
    }

}