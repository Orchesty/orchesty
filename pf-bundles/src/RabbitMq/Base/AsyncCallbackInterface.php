<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 25.9.17
 * Time: 16:24
 */

namespace Hanaboso\PipesFramework\RabbitMq\Base;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Interface AsyncCallbackInterface
 *
 * @package Hanaboso\PipesFramework\RabbitMqBundle\Consumer
 */
interface AsyncCallbackInterface
{

    /**
     * @param Message       $message
     * @param Channel       $channel
     * @param Client        $client
     * @param LoopInterface $loop
     *
     * @return mixed
     */
    public function processMessage(Message $message, Channel $channel, Client $client, LoopInterface $loop): PromiseInterface;

}