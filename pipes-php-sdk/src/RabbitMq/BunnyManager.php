<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Wire\AMQPTable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BunnyManager
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq
 */
class BunnyManager
{

    /**
     * @var string
     */
    private $clientServiceId;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var AMQPSocketConnection
     */
    private $client;

    /**
     * @var AMQPChannel|null
     */
    private $channel = NULL;

    /**
     * @var AMQPChannel|null
     */
    private $transactionalChannel;

    /**
     * @var mixed[]
     */
    private $config;

    /**
     * @var boolean
     */
    private $setUpComplete = FALSE;

    /**
     * BunnyManager constructor.
     *
     * @param ContainerInterface $container
     * @param string             $clientServiceId
     * @param mixed[]            $config
     */
    public function __construct(ContainerInterface $container, string $clientServiceId, array $config)
    {
        $this->container       = $container;
        $this->clientServiceId = $clientServiceId;
        $this->config          = $config;
    }

    /**
     * @return mixed[]
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return AMQPSocketConnection
     * @throws Exception
     */
    private function createClient(): AMQPSocketConnection
    {
        return new AMQPSocketConnection(...$this->config);
    }

    /**
     * @param bool $force
     *
     * @return AMQPSocketConnection
     * @throws Exception
     */
    public function getClient(bool $force = FALSE): AMQPSocketConnection
    {
        if ($force === TRUE) {
            $this->client = $this->createClient();
        }

        if ($this->client === NULL) {
            /** @var AMQPSocketConnection $client */
            $client       = $this->container->get($this->clientServiceId);
            $this->client = $client;
        }

        return $this->client;
    }

    /**
     * @return AMQPChannel
     * @throws Exception
     */
    public function createChannel(): AMQPChannel
    {
        if (!$this->getClient()->isConnected()) {
            $this->getClient()->reconnect();
        }

        return $this->getClient()->channel();
    }

    /**
     * @return AMQPChannel
     * @throws Exception
     */
    public function getChannel(): AMQPChannel
    {
        if (!$this->channel) {
            $this->channel = $this->createChannel();
        }

        return $this->channel;
    }

    /**
     * create/return transactional channel, where messages need to be commited
     *
     * @return AMQPChannel
     * @throws Exception
     */
    public function getTransactionalChannel(): AMQPChannel
    {
        if (!$this->transactionalChannel) {
            $this->transactionalChannel = $this->createChannel();

            // create transactional channel from normal one
            $this->transactionalChannel->tx_select();
        }

        return $this->transactionalChannel;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        if ($this->setUpComplete) {
            return;
        }

        $channel = $this->getChannel();

        foreach ($this->config['exchanges'] as $exchangeName => $exchangeDefinition) {
            /** @var mixed[] $arguments */
            $arguments = new AMQPTable($exchangeDefinition['arguments']);
            $channel->exchange_declare(
                $exchangeName,
                $exchangeDefinition['type'],
                FALSE,
                $exchangeDefinition['durable'],
                $exchangeDefinition['auto_delete'],
                $exchangeDefinition['internal'],
                FALSE,
                $arguments
            );
        }

        foreach ($this->config['exchanges'] as $exchangeName => $exchangeDefinition) {
            foreach ($exchangeDefinition['bindings'] as $binding) {
                /** @var mixed[] $arguments */
                $arguments = new AMQPTable($binding['arguments']);
                $channel->exchange_bind(
                    $exchangeName,
                    $binding['exchange'],
                    $binding['routing_key'],
                    FALSE,
                    $arguments
                );
            }
        }

        foreach ($this->config['queues'] as $queueName => $queueDefinition) {
            /** @var mixed[] $arguments */
            $arguments = new AMQPTable($queueDefinition['arguments']);
            $channel->queue_declare(
                $queueName,
                FALSE,
                $queueDefinition['durable'],
                $queueDefinition['exclusive'],
                $queueDefinition['auto_delete'],
                FALSE,
                $arguments
            );

            foreach ($queueDefinition['bindings'] as $binding) {
                /** @var mixed[] $arguments */
                $arguments = new AMQPTable($binding['arguments']);
                $channel->queue_bind(
                    $queueName,
                    $binding['exchange'],
                    $binding['routing_key'],
                    FALSE,
                    $arguments
                );
            }
        }

        $this->setUpComplete = TRUE;
    }

}
