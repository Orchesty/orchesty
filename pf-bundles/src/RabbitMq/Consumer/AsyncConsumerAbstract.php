<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.9.17
 * Time: 11:09
 */

namespace Hanaboso\PipesFramework\RabbitMq\Consumer;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class AsyncConsumerAbstract
 *
 * @package Hanaboso\PipesFramework\RabbitMqBundle\Consumer
 */
abstract class AsyncConsumerAbstract extends ConsumerAbstract
{

    /**
     * @var AsyncCallbackInterface
     */
    protected $callback;

    /**
     * @param AsyncCallbackInterface $callback
     *
     * @return AsyncConsumerAbstract
     */
    public function setCallback(AsyncCallbackInterface $callback): AsyncConsumerAbstract
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @param Message       $message
     * @param Channel       $channel
     * @param Client        $client
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     */
    public function processMessage(Message $message, Channel $channel, Client $client, LoopInterface $loop): PromiseInterface
    {
        return $this->callback->processMessage($message, $channel, $client, $loop);
    }

}