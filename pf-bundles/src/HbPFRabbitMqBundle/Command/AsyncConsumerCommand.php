<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 19.9.17
 * Time: 8:39
 */

namespace Hanaboso\PipesFramework\HbPFRabbitMqBundle\Command;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Exception;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DebugMessageTrait;
use Hanaboso\PipesFramework\RabbitMq\Consumer\AsyncConsumerAbstract;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Promise\all;
use function React\Promise\resolve;

/**
 * Class AsyncConsumerCommand
 *
 * @package Hanaboso\PipesFramework\RabbitMqBundle\Command
 */
class AsyncConsumerCommand extends Command implements LoggerAwareInterface
{

    use DebugMessageTrait;

    /**
     * @var int
     */
    private $timer = 2;

    /**
     * @var array
     */
    private $asyncConsumers;

    /**
     * @var array
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AsyncConsumerCommand constructor.
     *
     * @param array $asyncConsumers
     * @param array $config
     */
    public function __construct(array $asyncConsumers, array $config)
    {
        parent::__construct('rabbit-mq:async-consumer');
        $this->asyncConsumers = $asyncConsumers;
        $this->config         = $config;
        $this->logger         = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setDescription("Starts async consumer.")
            ->addArgument("consumer-name", InputArgument::REQUIRED, "Name of consumer.");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $consumerName = strtolower($input->getArgument("consumer-name"));

        if (!isset($this->asyncConsumers[$consumerName])) {
            throw new InvalidArgumentException(sprintf('Consumer \'%s\' doesn\'t exists.', $consumerName));
        }

        $this->startLoop($this->asyncConsumers[$consumerName]);
    }

    /**
     *
     */
    private function wait(): void
    {
        sleep($this->timer);

        if ($this->timer < 10) {
            $this->timer = $this->timer + 2;

            if ($this->timer > 10) {
                $this->timer = 10;
            }
        }
    }

    /**
     *
     */
    private function clearTimer(): void
    {
        $this->timer = 2;
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            'host'      => $this->config['host'],
            'vhost'     => $this->config['vhost'],
            'user'      => $this->config['user'],
            'password'  => $this->config['password'],
            'port'      => $this->config['port'],
            'heartbeat' => $this->config['heartbeat'],
        ];
    }

    /**
     * @param \Hanaboso\PipesFramework\RabbitMq\Consumer\AsyncConsumerAbstract $consumer
     */
    private function startLoop(AsyncConsumerAbstract $consumer): void
    {
        $eventLoop = Factory::create();

        $this->runAsyncConsumer($eventLoop, $consumer);

        try {
            $eventLoop->run();
        } catch (Exception $e) {
            $this->logger->error(sprintf('Loop crashed: %s', $e->getMessage()), ['exception' => $e]);

            $this->restart($eventLoop, $consumer);
        }
    }

    /**
     * @param LoopInterface                                                    $loop
     * @param \Hanaboso\PipesFramework\RabbitMq\Consumer\AsyncConsumerAbstract $consumer
     */
    public function restart(LoopInterface $loop, AsyncConsumerAbstract $consumer): void
    {
        $loop->stop();
        $this->wait();
        $this->startLoop($consumer);
    }

    /**
     * @param LoopInterface                                                    $loop
     * @param \Hanaboso\PipesFramework\RabbitMq\Consumer\AsyncConsumerAbstract $consumer
     *
     * @return PromiseInterface
     */
    public function connection(LoopInterface $loop, AsyncConsumerAbstract $consumer): PromiseInterface
    {
        $bunny = new Client($loop, $this->getOptions());

        return $bunny
            ->connect()
            ->then(function (Client $client) {
                $this->clearTimer();

                return $client->channel();
            }, function (Exception $e) use ($loop, $consumer): void {
                $this->logger->error(sprintf('RabbitMq connection error: %s', $e->getMessage()), ['exception' => $e]);

                $this->restart($loop, $consumer);
            });
    }

    /**
     * @param Channel                                                          $channel
     * @param \Hanaboso\PipesFramework\RabbitMq\Consumer\AsyncConsumerAbstract $asyncConsumer
     *
     * @return PromiseInterface
     */
    protected function setup(Channel $channel, AsyncConsumerAbstract $asyncConsumer): PromiseInterface
    {
        $this->logger->debug(sprintf(
            'Async consumer setup - queue: %s, prefetch_count: %s, prefetch_size: %s',
            $asyncConsumer->getQueue(),
            $asyncConsumer->getPrefetchCount(),
            $asyncConsumer->getPrefetchSize()
        ));

        return all([
            $channel->queueDeclare($asyncConsumer->getQueue()),
            $channel->qos($asyncConsumer->getPrefetchSize(), $asyncConsumer->getPrefetchCount()),
        ])->then(function () use ($channel): FulfilledPromise {
            return resolve($channel);
        });
    }

    /**
     * @param LoopInterface                                                    $loop
     * @param \Hanaboso\PipesFramework\RabbitMq\Consumer\AsyncConsumerAbstract $consumer
     */
    private function runAsyncConsumer(LoopInterface $loop, AsyncConsumerAbstract $consumer): void
    {
        $this->logger->debug(sprintf('Async consumer connected to %s:%s', $this->config['host'], $this->config['port']));

        $this
            ->connection($loop, $consumer)
            ->then(function (Channel $channel) use ($consumer) {
                return $this->setup($channel, $consumer);
            })
            ->then(function (Channel $channel) use ($loop, $consumer): void {
                $channel->consume(
                    function (Message $message, Channel $channel, Client $client) use ($loop, $consumer
                    ): PromiseInterface {

                        $this->logger->debug('Message received', $this->prepareBunnyMessage($message));

                        return $consumer
                            ->processMessage($message, $channel, $client, $loop)
                            ->then(function () use ($channel, $message): void {
                                $this->logger->debug('Message ACK', $this->prepareBunnyMessage($message));
                                $channel->ack($message);
                            }, function (Exception $e) use ($channel, $message): void {
                                $this->logger->error(
                                    sprintf('Async consumer error: %s', $e->getMessage()),
                                    $this->prepareBunnyMessage($message)
                                );
                                $this->logger->debug('Message NACK', $this->prepareBunnyMessage($message));
                                $channel->nack($message, FALSE, FALSE);
                            });
                    },
                    $consumer->getQueue()
                );
            });
    }

}