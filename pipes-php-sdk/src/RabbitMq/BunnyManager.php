<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Exception\BunnyException;
use Bunny\Protocol\MethodExchangeBindOkFrame;
use Bunny\Protocol\MethodExchangeDeclareOkFrame;
use Bunny\Protocol\MethodQueueBindOkFrame;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Exception;
use React\Promise\PromiseInterface;
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
     * @var Client
     */
    private $client;

    /**
     * @var Channel|null
     */
    private $channel = NULL;

    /**
     * @var Channel|null
     */
    private $transactionalChannel;

    /**
     * @var array
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
     * @param array              $config
     */
    public function __construct(ContainerInterface $container, string $clientServiceId, array $config)
    {
        $this->container       = $container;
        $this->clientServiceId = $clientServiceId;
        $this->config          = $config;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return Client
     */
    private function createClient(): Client
    {
        return new Client($this->config);
    }

    /**
     * @param bool $force
     *
     * @return Client
     */
    public function getClient(bool $force = FALSE): Client
    {
        if ($force === TRUE) {
            $this->client = $this->createClient();
        }

        if ($this->client === NULL) {
            /** @var Client $client */
            $client       = $this->container->get($this->clientServiceId);
            $this->client = $client;
        }

        return $this->client;
    }

    /**
     * @return Channel|PromiseInterface
     * @throws Exception
     */
    public function createChannel()
    {
        if (!$this->getClient()->isConnected()) {
            $this->getClient()->connect();
        }

        return $this->getClient()->channel();
    }

    /**
     * @return Channel
     * @throws Exception
     */
    public function getChannel(): Channel
    {
        if (!$this->channel) {
            /** @var Channel $ch */
            $ch            = $this->createChannel();
            $this->channel = $ch;
        }

        return $this->channel;
    }

    /**
     * create/return transactional channel, where messages need to be commited
     *
     * @return Channel
     * @throws BunnyException
     * @throws Exception
     */
    public function getTransactionalChannel(): Channel
    {
        if (!$this->transactionalChannel) {
            /** @var Channel $ch */
            $ch                         = $this->createChannel();
            $this->transactionalChannel = $ch;

            // create transactional channel from normal one
            try {
                $this->transactionalChannel->txSelect();
            } catch (Exception $e) {
                throw new BunnyException('Cannot create transaction channel.');
            }
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
            $frame = $channel->exchangeDeclare(
                $exchangeName,
                $exchangeDefinition['type'],
                FALSE,
                $exchangeDefinition['durable'],
                $exchangeDefinition['auto_delete'],
                $exchangeDefinition['internal'],
                FALSE,
                $exchangeDefinition['arguments']
            );

            if (!($frame instanceof MethodExchangeDeclareOkFrame)) {
                throw new BunnyException(sprintf('Could not declare exchange \'%s\'.', $exchangeName));
            }
        }

        foreach ($this->config['exchanges'] as $exchangeName => $exchangeDefinition) {
            foreach ($exchangeDefinition['bindings'] as $binding) {
                $frame = $channel->exchangeBind(
                    $exchangeName,
                    $binding['exchange'],
                    $binding['routing_key'],
                    FALSE,
                    $binding['arguments']
                );

                if (!($frame instanceof MethodExchangeBindOkFrame)) {
                    throw new BunnyException(
                        sprintf(
                            'Could not bind exchange \'%s\' to \'%s\' with routing key \'%s\'.',
                            $exchangeName,
                            $binding['exchange'],
                            $binding['routing_key']
                        )
                    );
                }
            }
        }

        foreach ($this->config['queues'] as $queueName => $queueDefinition) {
            $frame = $channel->queueDeclare(
                $queueName,
                FALSE,
                $queueDefinition['durable'],
                $queueDefinition['exclusive'],
                $queueDefinition['auto_delete'],
                FALSE,
                $queueDefinition['arguments']
            );

            if (!($frame instanceof MethodQueueDeclareOkFrame)) {
                throw new BunnyException(sprintf('Could not declare queue \'%s\'.', $queueName));
            }

            foreach ($queueDefinition['bindings'] as $binding) {
                $frame = $channel->queueBind(
                    $queueName,
                    $binding['exchange'],
                    $binding['routing_key'],
                    FALSE,
                    $binding['arguments']
                );

                if (!($frame instanceof MethodQueueBindOkFrame)) {
                    throw new BunnyException(
                        sprintf(
                            'Could not bind queue \'%s\' to \'%s\' with routing key \'%s\'.',
                            $queueName, $binding['exchange'], $binding['routing_key']
                        )
                    );
                }
            }
        }

        $this->setUpComplete = TRUE;
    }

}
