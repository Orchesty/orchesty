<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 12:42
 */

namespace RabbitMqBundle\Producer;

use Bunny\Exception\BunnyException;
use RabbitMqBundle\BunnyManager;
use RabbitMqBundle\ContentTypes;
use RabbitMqBundle\Serializers\IMessageSerializer;

class AbstractProducer
{

	/** @var string */
	private $exchange;

	/** @var string */
	private $routingKey;

	/** @var boolean */
	private $mandatory;

	/** @var boolean */
	private $immediate;

	/** @var string */
	private $serializerClassName;

	/** @var object */
	private $serializer;

	/** @var string */
	private $beforeMethod;

	/** @var string */
	private $contentType;

	/** @var BunnyManager */
	protected $manager;

	public function __construct(
		$exchange,
		$routingKey,
		$mandatory,
		$immediate,
		$serializerClassName,
		$beforeMethod,
		$contentType,
		BunnyManager $manager
	)
	{
		$this->exchange            = $exchange;
		$this->routingKey          = $routingKey;
		$this->mandatory           = $mandatory;
		$this->immediate           = $immediate;
		$this->serializerClassName = $serializerClassName;
		$this->beforeMethod        = $beforeMethod;
		$this->contentType         = $contentType;
		$this->manager             = $manager;
	}

	public function createMeta()
	{
		if ($this->serializerClassName) {
			/** @var MetaInterface $metaClassName */
			$metaClassName = $this->serializerClassName;

			return $metaClassName::getInstance();
		} else {
			return NULL;
		}
	}

	public function getMeta()
	{
		if ($this->serializer === NULL) {
			$this->serializer = $this->createMeta();
		}

		return $this->serializer;
	}

	/**
	 * @param       $message
	 * @param null  $routingKey
	 * @param array $headers
	 */
	public function publish($message, $routingKey = NULL, array $headers = [])
	{
		if (!$this->getMeta()) {
			throw new BunnyException("Could not create meta class {$this->serializerClassName}.");
		}

		if (is_string($message)) {
			$message = $this->serializer->fromJson($message);
		}

		if ($this->getBeforeMethod()) {
			$this->{$this->beforeMethod}($message, $this->manager->getChannel());
		}

		switch ($this->getContentType()) {
			case ContentTypes::APPLICATION_JSON:
				if ($this->serializer instanceof IMessageSerializer) {
					$message = $this->serializer->toJson($message);
				} else {
					throw new BunnyException("Cannot serialize message to JSON.");
				}
				break;

			default:
				throw new BunnyException("Unhandled content type '{$this->contentType}'.");
		}

		if ($routingKey === NULL) {
			$routingKey = $this->routingKey;
		}

		$headers["content-type"] = $this->contentType;

		$this->manager->getChannel()->publish(
			$message,
			$headers,
			$this->exchange,
			$routingKey,
			$this->mandatory,
			$this->immediate
		);
	}

	/**
	 * @return string
	 */
	public function getExchange(): string
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
	 * @return bool
	 */
	public function isMandatory(): bool
	{
		return $this->mandatory;
	}

	/**
	 * @return bool
	 */
	public function isImmediate(): bool
	{
		return $this->immediate;
	}

	/**
	 * @return string
	 */
	public function getSerializerClassName(): string
	{
		return $this->serializerClassName;
	}

	/**
	 * @return NULL|IMessageSerializer
	 */
	//TODO: add return type
	public function getSerializer()
	{
		return $this->serializer;
	}

	/**
	 * @return string
	 */
	public function getBeforeMethod(): string
	{
		return $this->beforeMethod;
	}

	/**
	 * @return string
	 */
	public function getContentType(): string
	{
		return $this->contentType;
	}

	/**
	 * @return BunnyManager
	 */
	public function getManager(): BunnyManager
	{
		return $this->manager;
	}

}
