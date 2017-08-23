<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 9:04
 */

namespace RabbitMqBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RabbitMqCompilerPass implements CompilerPassInterface
{

	/** @var string */
	private $configKey;

	/** @var string */
	private $clientServiceId;

	/** @var string */
	private $managerServiceId;

	/** @var string */
	private $channelServiceId;

	/** @var string */
	private $setupCommandServiceId;

	/** @var string */
	private $consumerCommandServiceId;

	/** @var string */
	private $producerCommandServiceId;

	/**
	 * RabbitMqCompilerPass constructor.
	 *
	 * @param $configKey
	 * @param $clientServiceId
	 * @param $managerServiceId
	 * @param $channelServiceId
	 * @param $setupCommandServiceId
	 * @param $consumerCommandServiceId
	 * @param $producerCommandServiceId
	 */
	public function __construct(
		$configKey,
		$clientServiceId,
		$managerServiceId,
		$channelServiceId,
		$setupCommandServiceId,
		$consumerCommandServiceId,
		$producerCommandServiceId)
	{

		$this->configKey                = $configKey;
		$this->clientServiceId          = $clientServiceId;
		$this->managerServiceId         = $managerServiceId;
		$this->channelServiceId         = $channelServiceId;
		$this->setupCommandServiceId    = $setupCommandServiceId;
		$this->consumerCommandServiceId = $consumerCommandServiceId;
		$this->producerCommandServiceId = $producerCommandServiceId;
	}

	/**
	 * You can modify the container here before it is dumped to PHP code.
	 *
	 * @param ContainerBuilder $container
	 */
	public function process(ContainerBuilder $container)
	{
		if (!$container->hasParameter($this->configKey)) {
			throw new InvalidArgumentException("Container doesn't have parameter '{$this->configKey}', RabbitMqBunnyExtension probably haven't processed config.");
		}

		$config = $container->getParameter($this->configKey);

		$consumers = [];
		$producers = [];

		if (!array_key_exists('producers', $config)) {
			throw new InvalidArgumentException("Container doesn't have config parameter 'producers', RabbitMqBunnyExtension probably haven't processed config.");
		}

		foreach ($config['producers'] as $key => $value) {

			$definition = new Definition($value['class'], [
				$value['exchange'],
				$value['routing_key'],
				$value['mandatory'],
				$value['immediate'],
				$value['serializer'],
				$value['before_method'],
				$value['content_type'],
				new Reference($this->managerServiceId),
			]);

			$producers[$key] = $definition;

			$serviceName = sprintf('rabbit-mq.producer.%s', $key);
			$container->setDefinition($serviceName, $definition);
		}

		if (!array_key_exists('consumers', $config)) {
			throw new InvalidArgumentException("Container doesn't have config parameter 'consumers', RabbitMqBunnyExtension probably haven't processed config.");
		}

		foreach ($config['consumers'] as $key => $value) {

			$definition = new Definition($value['class'], [
				$value['exchange'],
				$value['routing_key'],
				$value['queue'],
				$value['consumer_tag'],
				$value['no_local'],
				$value['no_ack'],
				$value['exclusive'],
				$value['nowait'],
				$value['arguments'],
				$value['prefetch_count'],
				$value['prefetch_size'],
				$value['serializer'],
				$value['set_up_method'],
				$value['tick_method'],
				$value['tick_seconds'],
				$value['max_messages'],
				$value['max_seconds']
			]);

			$consumers[$key] = $definition;

			$serviceName = sprintf('rabbit-mq.consumer.%s', $key);
			$container->setDefinition($serviceName, $definition);
		}

		/**
		 * Connection definition
		 */
		$container->setDefinition($this->clientServiceId, new Definition('%rabbit-mq.bunny-client%', [
			[
				'host'      => $config["host"],
				'port'      => $config["port"],
				'vhost'     => $config["vhost"],
				'user'      => $config["user"],
				'password'  => $config["password"],
				'heartbeat' => $config["heartbeat"],
			],
		]));

		/**
		 * BunnyManager
		 */
		$container->setDefinition($this->managerServiceId, new Definition('%rabbit-mq.bunny-manager%', [
			new Reference('service_container'),
			$this->clientServiceId,
			$config,
		]));
		/**
		 * Bunny channel
		 */
		$channel = new Definition('%rabbit-mq.bunny-channel%');
		$channel->setFactory([new Reference($this->managerServiceId), "getChannel"]);
		$container->setDefinition($this->channelServiceId, $channel);

		$container->setDefinition($this->setupCommandServiceId,
			new Definition('%rabbit-mq.command.setup%', [
				new Reference($this->managerServiceId),
			]));

		$container->setDefinition($this->consumerCommandServiceId,
			new Definition('%rabbit-mq.command.consumer%', [
				new Reference("service_container"),
				new Reference($this->managerServiceId),
				$consumers,
			]));

//		$container->setDefinition($this->producerCommandServiceId,
//			new Definition('%rabbit-mq.command.producer%', [
//				new Reference("service_container"),
//				new Reference($this->managerServiceId),
//				$producers,
//			]));
	}

}
