<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.9.17
 * Time: 16:46
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\AsyncDemo;

use Bunny\Message;
use Clue\React\Buzz\Browser;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchActionInterface;
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
class DemoCallback implements BatchActionInterface
{

    /**
     * @param Message       $message
     * @param LoopInterface $loop
     * @param callable      $itemCallBack
     *
     * @return PromiseInterface
     */
    public function batchAction(Message $message, LoopInterface $loop, callable $itemCallBack): PromiseInterface
    {
        $browser = new Browser($loop);

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
                })->then($itemCallBack);
        }

        return all($requests);
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