<?php

namespace RabbitMqBundle;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Exception\BunnyException;
use Bunny\Protocol\MethodExchangeBindOkFrame;
use Bunny\Protocol\MethodExchangeDeclareOkFrame;
use Bunny\Protocol\MethodQueueBindOkFrame;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BunnyManager
{

	/**
	 * @var string
	 */
	private $clientServiceId;

	/** @var ContainerInterface */
	private $container;

	/** @var Client */
	private $client;

	/** @var Channel */
	private $channel;

	/** @var  Channel */
	private $transactionalChannel;

	/** @var array */
	private $config;

	/** @var boolean */
	private $setUpComplete = FALSE;

	public function __construct(ContainerInterface $container, $clientServiceId, array $config)
	{
		$this->container       = $container;
		$this->clientServiceId = $clientServiceId;
		$this->config          = $config;
	}

	public function getClient()
	{
		if ($this->client === NULL) {
			$this->client = $this->container->get($this->clientServiceId);
		}

		return $this->client;
	}

	public function createChannel()
	{
		if (!$this->getClient()->isConnected()) {
			$this->getClient()->connect();
		}

		return $this->getClient()->channel();
	}

	public function getChannel()
	{
		if (!$this->channel) {
			$this->channel = $this->createChannel();
		}

		return $this->channel;
	}

	/**
	 * create/return transactional channel, where messages need to be commited
	 *
	 * @throws BunnyException
	 * @return Channel|\React\Promise\PromiseInterface
	 */
	public function getTransactionalChannel()
	{
		if (!$this->transactionalChannel) {
			$this->transactionalChannel = $this->createChannel();

			// create transactional channel from normal one
			try {
				$this->transactionalChannel->txSelect();
			} catch (\Exception $e) {
				throw new BunnyException("Cannot create transaction channel.");
			}
		}

		return $this->transactionalChannel;
	}

	public function setUp()
	{
		if ($this->setUpComplete) {
			return;
		}

		$channel = $this->getChannel();

		foreach ($this->config["exchanges"] as $exchangeName => $exchangeDefinition) {
			$frame = $channel->exchangeDeclare(
				$exchangeName,
				$exchangeDefinition["type"],
				FALSE,
				$exchangeDefinition["durable"],
				$exchangeDefinition["auto_delete"],
				$exchangeDefinition["internal"],
				FALSE,
				$exchangeDefinition["arguments"]
			);

			if (!($frame instanceof MethodExchangeDeclareOkFrame)) {
				throw new BunnyException("Could not declare exchange '{$exchangeName}'.");
			}
		}

		foreach ($this->config["exchanges"] as $exchangeName => $exchangeDefinition) {
			foreach ($exchangeDefinition["bindings"] as $binding) {
				$frame = $channel->exchangeBind(
					$exchangeName,
					$binding["exchange"],
					$binding["routing_key"],
					FALSE,
					$binding["arguments"]
				);

				if (!($frame instanceof MethodExchangeBindOkFrame)) {
					throw new BunnyException(
						"Could not bind exchange '{$exchangeName}' to '{$binding["exchange"]}' with routing key '{$binding["routing_key"]}'."
					);
				}
			}
		}

		foreach ($this->config["queues"] as $queueName => $queueDefinition) {
			$frame = $channel->queueDeclare(
				$queueName,
				FALSE,
				$queueDefinition["durable"],
				$queueDefinition["exclusive"],
				$queueDefinition["auto_delete"],
				FALSE,
				$queueDefinition["arguments"]
			);

			if (!($frame instanceof MethodQueueDeclareOkFrame)) {
				throw new BunnyException("Could not declare queue '{$queueName}'.");
			}

			foreach ($queueDefinition["bindings"] as $binding) {
				$frame = $channel->queueBind(
					$queueName,
					$binding["exchange"],
					$binding["routing_key"],
					FALSE,
					$binding["arguments"]
				);

				if (!($frame instanceof MethodQueueBindOkFrame)) {
					throw new BunnyException(
						"Could not bind queue '{$queueName}' to '{$binding["exchange"]}' with routing key '{$binding["routing_key"]}'."
					);
				}
			}
		}

		$this->setUpComplete = TRUE;
	}

}
