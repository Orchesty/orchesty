<?php
/**
 * Created by PhpStorm.
 * User: sep
 * Date: 22.8.17
 * Time: 13:46
 */

namespace Commons\RabbitMq\Demo;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use RabbitMqBundle\Consumer\AbstractConsumer;

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
	public function handle($data, Message $message, Channel $channel, Client $client)
	{
		// TODO: Implement handle() method.
	}

}
