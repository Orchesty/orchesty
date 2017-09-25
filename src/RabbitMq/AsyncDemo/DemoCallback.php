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

/**
 * Class DemoCallback
 *
 * @package Hanaboso\PipesFramework\Commons\RabbitMq\AsyncDemo
 */
class DemoCallback implements AsyncCallbackInterface
{

    /**
     * @param Message       $message
     * @param Channel       $channel
     * @param Client        $client
     * @param LoopInterface $loop
     *
     * @return mixed
     * @throws Exception
     */
    public function processMessage(Message $message, Channel $channel, Client $client, LoopInterface $loop): Promise
    {
        $queueName = $message->getHeader('reply-to');
        if ($queueName === '' || $queueName === NULL) {
            throw new Exception('Missing "reply-to" header');
        }

        $browser = new Browser($loop);

        return $client->channel()
            ->then(function (Channel $channel) use ($queueName): PromiseInterface {
                return $channel
                    ->queueDeclare($queueName)
                    ->then(function () use ($channel): Channel {
                        return $channel;
                    });
            })
            ->then(function (Channel $channel) use ($browser, $message): PromiseInterface {
                return all($this->getRequests($browser, $message, $channel))
                    ->then(function () use ($channel): Channel {
                        return $channel;
                    });
            })
            ->then(function (Channel $channel) use ($message): PromiseInterface {
                return $this->batchCallback($channel, $message);
            })
            ->otherwise(function (Exception $e): void {
                throw new Exception('Error: ' . $e->getMessage(), $e->getCode(), $e);
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
            [
                'type'           => 'batch_item',
                'correlation-id' => $message->getHeader('correlation-id'),
            ],
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
            [
                'type'           => 'batch_total',
                'correlation-id' => $message->getHeader('correlation-id'),
            ],
            '',
            $message->getHeader('reply-to')
        )->then(function (): void {
            echo 'Bath total published' . PHP_EOL;
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
                    if ($response->getHeader('content-type') == 'apllication/json') {
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