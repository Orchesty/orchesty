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
use Hanaboso\PipesFramework\RabbitMq\Base\AsyncConsumerAbstract;
use InvalidArgumentException;
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
class AsyncConsumerCommand extends Command
{

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
     *
     * @return int|null|void
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
     * @param AsyncConsumerAbstract $consumer
     */
    private function startLoop(AsyncConsumerAbstract $consumer): void
    {
        $eventLoop = Factory::create();

        $this->runAsyncConsumer($eventLoop, $consumer);

        try {
            $eventLoop->run();
        } catch (Exception $e) {
            echo 'Loop crashed: ' . $e->getMessage() . PHP_EOL;

            $this->restart($eventLoop, $consumer);
        }
    }

    /**
     * @param LoopInterface         $loop
     * @param AsyncConsumerAbstract $consumer
     */
    public function restart(LoopInterface $loop, AsyncConsumerAbstract $consumer): void
    {
        $loop->stop();
        $this->wait();
        $this->startLoop($consumer);
    }

    /**
     * @param LoopInterface         $loop
     * @param AsyncConsumerAbstract $consumer
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
                echo 'Can not connect to rabbitmq.' . $e->getMessage() . PHP_EOL;

                $this->restart($loop, $consumer);
            });
    }

    /**
     * @param Channel               $channel
     * @param AsyncConsumerAbstract $asyncConsumer
     *
     * @return PromiseInterface
     */
    protected function setup(Channel $channel, AsyncConsumerAbstract $asyncConsumer): PromiseInterface
    {
        return all([
            $channel->queueDeclare($asyncConsumer->getQueue()),
            $channel->qos($asyncConsumer->getPrefetchSize(), $asyncConsumer->getPrefetchCount()),
        ])->then(function () use ($channel): FulfilledPromise {
            return resolve($channel);
        });
    }

    /**
     * @param LoopInterface         $loop
     * @param AsyncConsumerAbstract $consumer
     */
    private function runAsyncConsumer(LoopInterface $loop, AsyncConsumerAbstract $consumer): void
    {
        echo 'Connecting ...' . PHP_EOL;

        $this
            ->connection($loop, $consumer)
            ->then(function (Channel $channel) use ($consumer) {
                return $this->setup($channel, $consumer);
            })
            ->then(function (Channel $channel) use ($loop, $consumer): void {

                echo 'Before consume' . PHP_EOL;

                $channel->consume(
                    function (Message $message, Channel $channel, Client $client) use ($loop, $consumer
                    ): PromiseInterface {

                        echo 'Message received.' . PHP_EOL;

                        return $consumer
                            ->processMessage($message, $channel, $client, $loop)
                            ->then(function () use ($channel, $message): void {
                                echo 'Message ACK.' . PHP_EOL;
                                $channel->ack($message);
                            }, function (Exception $e) use ($channel, $message): void {
                                echo 'Message error: ' . $e->getMessage() . PHP_EOL;
                                echo 'Message NACK.' . PHP_EOL;
                                $channel->nack($message, FALSE, FALSE);
                            });
                    },
                    $consumer->getQueue()
                );
            });
    }

}