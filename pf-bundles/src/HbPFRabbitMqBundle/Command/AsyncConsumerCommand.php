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
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Promise\all;

/**
 * Class AsyncConsumerCommand
 *
 * @package Hanaboso\PipesFramework\RabbitMqBundle\Command
 */
class AsyncConsumerCommand extends Command
{

    public const INPUT_QUEUE = 'input_queue';

    /**
     * @var int
     */
    private $timer = 1;

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
            $this->getChannelPromise($channel),
        ]);
    }

    /**
     * @param Channel $channel
     *
     * @return Promise
     */
    private function getChannelPromise(Channel $channel): Promise
    {
        return new Promise(function ($resolve) use ($channel): void {
            $resolve($channel);
        });
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
     *
     */
    private function consecutiveWait(): void
    {
        sleep($this->timer);
    }

    /**
     *
     */
    private function resetConsecutiveTimer(): void
    {
        $this->timer = 1;
    }

    /**
     * @return int
     */
    private function increaseConsecutiveTimer(): int
    {
        if ($this->timer < 10) {
            $this->timer = $this->timer * 2;
        }

        return $this->timer;
    }

    /**
     * @param AsyncConsumerAbstract $consumerAbstract
     */
    private function startLoop(AsyncConsumerAbstract $consumerAbstract): void
    {
        $eventLoop = Factory::create();

        $this->runAsyncConsumer($eventLoop, $consumerAbstract);

        try {
            $eventLoop->run();
        } catch (Exception $e) {
            var_dump("Loop crashed.", $e->getMessage());

            $this->consecutiveWait();
            $this->startLoop($consumerAbstract);
        }
    }

    /**
     * @param LoopInterface         $loop
     * @param AsyncConsumerAbstract $consumer
     */
    private function runAsyncConsumer(LoopInterface $loop, AsyncConsumerAbstract $consumer): void
    {
        echo 'Connecting ...' . PHP_EOL;

        $bunny = new Client($loop, $this->getOptions());
        $bunny
            ->connect()
            ->then(function (Client $client) {
                $this->resetConsecutiveTimer();

                return $client->channel();
            }, function (Exception $e) use ($loop, $consumer) {
                echo 'Can not connect to rabbitmq.' . $e->getMessage() . PHP_EOL;

                $loop->stop();
                $this->consecutiveWait();
                $this->increaseConsecutiveTimer();
                $this->startLoop($consumer);
            })
            ->then(function (Channel $channel) use ($consumer) {
                return $this->setup($channel, $consumer);
            })
            ->then(function (array $all) use ($loop, $consumer): void {

                echo 'Before consume' . PHP_EOL;

                $channel = $all[2];

                $channel->consume(
                    function (Message $message, Channel $channel, Client $client) use ($loop, $consumer
                    ): PromiseInterface {

                        echo 'Message received.' . PHP_EOL;

                        return $consumer
                            ->processMessage($message, $channel, $client, $loop)
                            ->then(function () use ($channel, $message): void {
                                echo 'Message ACK.' . PHP_EOL;
                                $channel->ack($message);
                            })->otherwise(function () use ($channel, $message): void {
                                echo 'Message NACK.' . PHP_EOL;
                                $channel->nack($message);
                            });
                    },
                    $consumer->getQueue()
                );
            });
    }

}