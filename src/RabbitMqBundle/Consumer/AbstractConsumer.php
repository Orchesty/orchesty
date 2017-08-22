<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 12:57
 */

namespace RabbitMqBundle\Consumer;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;

/**
 * Class AbstractConsumer
 *
 * @package RabbitMqBundle\Consumer
 */
abstract class AbstractConsumer
{

	/**
	 * @var null
	 */
	private $exchange;
	/**
	 * @var string
	 */
	private $routingKey;
	/**
	 * @var null
	 */
	private $queue;
	/**
	 * @var string
	 */
	private $consumerTag;
	/**
	 * @var bool
	 */
	private $noLocal;
	/**
	 * @var bool
	 */
	private $noAck;
	/**
	 * @var bool
	 */
	private $exclusive;
	/**
	 * @var bool
	 */
	private $nowait;
	/**
	 * @var array
	 */
	private $arguments;
	/**
	 * @var null
	 */
	private $prefetchCount;
	/**
	 * @var null
	 */
	private $prefetchSize;
	/**
	 * @var null
	 */
	private $serializer;
	/**
	 * @var null
	 */
	private $setUpMethod;
	/**
	 * @var null
	 */
	private $tickMethod;
	/**
	 * @var null
	 */
	private $tickSeconds;
	/**
	 * @var null
	 */
	private $maxMessages;
	/**
	 * @var null
	 */
	private $maxSeconds;

	/**
	 * AbstractConsumer constructor.
	 *
	 * @param null   $exchange
	 * @param string $routingKey
	 * @param null   $queue
	 * @param string $consumerTag
	 * @param bool   $noLocal
	 * @param bool   $noAck
	 * @param bool   $exclusive
	 * @param bool   $nowait
	 * @param array  $arguments
	 * @param null   $prefetchCount
	 * @param null   $prefetchSize
	 * @param null   $serializer
	 * @param null   $setUpMethod
	 * @param null   $tickMethod
	 * @param null   $tickSeconds
	 * @param null   $maxMessages
	 * @param null   $maxSeconds
	 */
	public function __construct(
		$exchange = NULL,
		$routingKey = '',
		$queue = NULL,
		$consumerTag = '',
		$noLocal = FALSE,
		$noAck = FALSE,
		$exclusive = FALSE,
		$nowait = FALSE,
		$arguments = [],
		$prefetchCount = NULL,
		$prefetchSize = NULL,
		$serializer = NULL,
		$setUpMethod = NULL,
		$tickMethod = NULL,
		$tickSeconds = NULL,
		$maxMessages = NULL,
		$maxSeconds = NULL
	)
	{
		$this->exchange      = $exchange;
		$this->routingKey    = $routingKey;
		$this->queue         = $queue;
		$this->consumerTag   = $consumerTag;
		$this->noLocal       = $noLocal;
		$this->noAck         = $noAck;
		$this->exclusive     = $exclusive;
		$this->nowait        = $nowait;
		$this->arguments     = $arguments;
		$this->prefetchCount = $prefetchCount;
		$this->prefetchSize  = $prefetchSize;
		$this->serializer    = $serializer;
		$this->setUpMethod   = $setUpMethod;
		$this->tickMethod    = $tickMethod;
		$this->tickSeconds   = $tickSeconds;
		$this->maxMessages   = $maxMessages;
		$this->maxSeconds    = $maxSeconds;
	}

	/**
	 * @param         $data
	 * @param Message $message
	 * @param Channel $channel
	 * @param Client  $client
	 */
	public function handleMessage($data, Message $message, Channel $channel, Client $client)
	{
		$this->handle($data, $message, $channel, $client);
	}

	/**
	 * @param mixed   $data
	 * @param Message $message
	 * @param Channel $channel
	 * @param Client  $client
	 *
	 * @return mixed
	 */
	abstract public function handle($data, Message $message, Channel $channel, Client $client);

	/**
	 * @return null
	 */
	public function getExchange()
	{
		return $this->exchange;
	}

	/**
	 * @return string
	 */
	public function getRoutingKey(): string
	{
		return $this->routingKey;
	}

	/**
	 * @return null
	 */
	public function getQueue()
	{
		return $this->queue;
	}

	/**
	 * @return string
	 */
	public function getConsumerTag(): string
	{
		return $this->consumerTag;
	}

	/**
	 * @return bool
	 */
	public function isNoLocal(): bool
	{
		return $this->noLocal;
	}

	/**
	 * @return bool
	 */
	public function isNoAck(): bool
	{
		return $this->noAck;
	}

	/**
	 * @return bool
	 */
	public function isExclusive(): bool
	{
		return $this->exclusive;
	}

	/**
	 * @return bool
	 */
	public function isNowait(): bool
	{
		return $this->nowait;
	}

	/**
	 * @return array
	 */
	public function getArguments(): array
	{
		return $this->arguments;
	}

	/**
	 * @return null
	 */
	public function getPrefetchCount()
	{
		return $this->prefetchCount;
	}

	/**
	 * @return null
	 */
	public function getPrefetchSize()
	{
		return $this->prefetchSize;
	}

	/**
	 * @return null
	 */
	public function getSerializer()
	{
		return $this->serializer;
	}

	/**
	 * @return null
	 */
	public function getSetUpMethod()
	{
		return $this->setUpMethod;
	}

	/**
	 * @return null
	 */
	public function getTickMethod()
	{
		return $this->tickMethod;
	}

	/**
	 * @return null
	 */
	public function getTickSeconds()
	{
		return $this->tickSeconds;
	}

	/**
	 * @return null
	 */
	public function getMaxMessages()
	{
		return $this->maxMessages;
	}

	/**
	 * @return null
	 */
	public function getMaxSeconds()
	{
		return $this->maxSeconds;
	}

	/**
	 * @param null $queue
	 */
	public function setQueue($queue)
	{
		$this->queue = $queue;
	}

}
