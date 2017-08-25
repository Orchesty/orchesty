<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 13:46
 */

namespace Hanaboso\PipesFramework\Commons\RabbitMq\Demo;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Hanaboso\PipesFramework\RabbitMqBundle\Consumer\AbstractConsumer;

/**
 * Class DemoConsumer
 *
 * @package Hanaboso\PipesFramework\Commons\RabbitMq\Demo
 */
class DemoConsumer extends AbstractConsumer
{

    /**
     * @param mixed   $data
     * @param Message $message
     * @param Channel $channel
     * @param Client  $client
     *
     * @return mixed
     */
    public function handle($data, Message $message, Channel $channel, Client $client): void
    {
        $channel->ack($message);
    }

}
