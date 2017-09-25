<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.9.17
 * Time: 11:09
 */

namespace Hanaboso\PipesFramework\RabbitMq\Base;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMq\Base\AsyncCallbackInterface;
use Hanaboso\PipesFramework\RabbitMq\Base\ConsumerAbstract;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

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
     * @return Promise
     */
    public function processMessage(Message $message, Channel $channel, Client $client, LoopInterface $loop): Promise
    {
        return $this->callback->processMessage($message, $channel, $client, $loop);
    }

}