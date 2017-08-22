<?php

namespace RabbitMqBundle\Command;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Bunny\Protocol\MethodBasicQosOkFrame;
use Bunny\Protocol\MethodQueueBindOkFrame;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use RabbitMqBundle\BunnyManager;
use RabbitMqBundle\Consumer\AbstractConsumer;
use RabbitMqBundle\Serializers\IMessageSerializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConsumerCommand extends Command
{

	/** @var ContainerInterface */
	private $container;

	/** @var BunnyManager */
	private $manager;

	/** @var Consumer[][] */
	private $consumers;

	/** @var int */
	private $messages = 0;

	public function __construct(ContainerInterface $container, BunnyManager $manager, array $consumers)
	{
		parent::__construct("rabbit-mq:consumer");
		$this->container = $container;
		$this->manager   = $manager;
		$this->consumers = $consumers;
	}

	protected function configure()
	{
		$this
			->setDescription("Starts given consumer.")
			->addArgument("consumer-name", InputArgument::REQUIRED, "Name of consumer.")
			->addArgument("consumer-parameters", InputArgument::IS_ARRAY, "Argv input to consumer.", []);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$consumerName = strtolower($input->getArgument("consumer-name"));

		if (!isset($this->consumers[$consumerName])) {
			throw new \InvalidArgumentException("Consumer '{$consumerName}' doesn't exists.");
		}

		$consumerArgv = $input->getArgument("consumer-parameters");
		array_unshift($consumerArgv, $consumerName);
		$this->manager->setUp();

		$channel = $this->manager->getChannel();

		/** @var AbstractConsumer $consumer */
		$consumer     = $this->consumers[$consumerName];
		$maxMessages  = PHP_INT_MAX;
		$maxSeconds   = PHP_INT_MAX;
		$calledSetUps = [];
		$tickMethod   = NULL;
		$tickSeconds  = NULL;

		$maxMessages = min($maxMessages, $consumer->getMaxMessages() ?: PHP_INT_MAX);
		$maxSeconds  = min($maxSeconds, $consumer->getMaxSeconds() ?: PHP_INT_MAX);

		if (empty($consumer->getQueue())) {
			$queueOk = $channel->queueDeclare("", FALSE, FALSE, TRUE);
			if (!($queueOk instanceof MethodQueueDeclareOkFrame)) {
				throw new BunnyException("Could not declare anonymous queue.");
			}

			$consumer->setQueue($queueOk->queue);

			$bindOk = $channel->queueBind($consumer->getQueue(), $consumer->getExchange(), $consumer->getRoutingKey());
			if (!($bindOk instanceof MethodQueueBindOkFrame)) {
				throw new BunnyException("Could not bind anonymous queue.");
			}
		}

		if ($consumer->getPrefetchSize() || $consumer->getPrefetchCount()) {
			$qosOk = $channel->qos($consumer->getPrefetchSize(), $consumer->getPrefetchCount());
			if (!($qosOk instanceof MethodBasicQosOkFrame)) {
				throw new BunnyException("Could not set prefetch-size/prefetch-count.");
			}
		}

		$serializer = NULL;
		if ($consumer->getSerializer()) {
			/** @var IMessageSerializer $metaClassName */
			$metaClassName = $consumer->getSerializer();

			if (!class_exists($metaClassName)) {
				throw new BunnyException("Consumer meta class {$metaClassName} does not exist.");
			}

			if (!method_exists($metaClassName, "getInstance")) {
				throw new BunnyException("Method {$metaClassName}::getInstance() does not exist.");
			}

			$serializer = $metaClassName::getInstance();
		}

		if ($consumer->getSetUpMethod() && !isset($calledSetUps[$consumer->getSetUpMethod()])) {
			if (!method_exists($consumer, $consumer->getSetUpMethod())) {
				throw new BunnyException(
					"Init method " . get_class($consumer) . "::{$consumer->getSetUpMethod()} does not exist."
				);
			}

			$consumer->{$consumer->getSetUpMethod()}($channel, $channel->getClient(), $consumerArgv);
			$calledSetUps[$consumer->getSetUpMethod()] = TRUE;
		}

		if ($consumer->getTickMethod()) {
			if ($tickMethod) {
				if ($consumer->getTickMethod() !== $tickMethod) {
					throw new BunnyException(
						"Only single tick method is supported - " . get_class($consumer) . "."
					);
				}

				if ($consumer->getTickSeconds() !== $tickSeconds) {
					throw new BunnyException(
						"Only single tick seconds is supported - " . get_class($consumer) . "."
					);
				}

			} else {
				if (!$consumer->getTickSeconds()) {
					throw new BunnyException(
						"If you specify 'tickMethod', you have to specify 'tickSeconds' - " . get_class($consumer) . "."
					);
				}

				if (!method_exists($consumer, $consumer->getTickMethod())) {
					throw new BunnyException(
						"Tick method " . get_class($consumer) . "::{$consumer->getTickMethod()} does not exist."
					);
				}

				$tickMethod  = $consumer->getTickMethod();
				$tickSeconds = $consumer->getTickSeconds();
			}
		}

		$channel->consume(
			function (Message $message, Channel $channel, Client $client) use ($consumer, $serializer) {
				$this->handleMessage($consumer, $serializer, $message, $channel, $client);
			},
			$consumer->getQueue(),
			$consumer->getConsumerTag(),
			$consumer->isNoLocal(),
			$consumer->isNoAck(),
			$consumer->isExclusive(),
			$consumer->isNowait(),
			$consumer->getArguments()
		);

		$startTime = microtime(TRUE);

		while (microtime(TRUE) < $startTime + $maxSeconds && $this->messages < $maxMessages) {
			$channel->getClient()->run($tickSeconds ?: $maxSeconds);
			if ($tickMethod) {
				$consumer->{$tickMethod}($channel, $channel->getClient());
			}
		}
		$channel->getClient()->disconnect();
	}

	public function handleMessage(
		AbstractConsumer $consumer,
		$serializer = NULL,
		Message $message,
		Channel $channel,
		Client $client
	)
	{
		$data = $message->content;
		if ($serializer) {
			switch ($message->getHeader("content-type")) {
				case ContentTypes::APPLICATION_JSON:
					if ($serializer instanceof IMessageSerializer) {
						$data = $serializer->fromJson($data);
					} else {
						throw new BunnyException("Meta class does not support JSON.");
					}
					break;

				default:
					throw new BunnyException("Message does not have 'content-type' header, cannot deserialize data.");
			}
		}

		$consumer->handleMessage($data, $message, $channel, $client);

		if ($consumer->getMaxMessages() !== NULL && ++$this->messages >= $consumer->getMaxMessages()) {
			$client->stop();
		}
	}

}
