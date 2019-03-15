<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Consumer;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use React\EventLoop\LoopInterface;

/**
 * Interface AsyncCallbackInterface
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Consumer
 */
interface AsyncCallbackInterface
{

    /**
     * @param Message       $message
     * @param Channel       $consumerChannel
     * @param Client        $client
     * @param LoopInterface $loop
     *
     * @return mixed
     */
    public function processMessage(Message $message, Channel $consumerChannel, Client $client, LoopInterface $loop);

}
