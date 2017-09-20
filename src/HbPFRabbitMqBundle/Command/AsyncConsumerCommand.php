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
use Clue\React\Buzz\Browser;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Psr\Container\ContainerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Command\Command;
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
     * @var ContainerInterface
     */
    private $container;

    /**
     * AsyncConsumerCommand constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct('amq:async');
        $this->container = $container;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this->setDescription("Starts async consumer.");
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return Promise
     */
    protected function fetchData(string $method, string $url): Promise
    {
        return new Promise(function (callable $resolve, callable $reject) use ($method, $url) {
            $guzzle   = new GuzzleClient();
            $response = $guzzle->request($method, $url);

            if ($response->getStatusCode() === 200) {
                $resolve($response);
            } else {
                $reject($response);
            }
        });
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->startLoop();

    }

    private function consecutiveWait()
    {
        // TODO - set wait dynamically 1...2...4...8...16s
        sleep(2);
    }

    private function startLoop()
    {
        $eventLoop = Factory::create();

        $this->runAsyncConsumer($eventLoop);

        try {
            $eventLoop->run();
        } catch (\Exception $e) {
            var_dump("Loop crashed.", $e->getMessage());

            $this->consecutiveWait();
            $this->startLoop();
        }
    }

    private function runAsyncConsumer(LoopInterface $eventLoop): void
    {
        $options = [
            'host'  => 'rabbitmq',
            'vhost' => '/',
            'user'  => 'guest',
            'pass'  => 'guest',
        ];

        // TODO - use separate channels for publisher/consumer

        var_dump("Connecting...");

        $browser = new Browser($eventLoop);
        $bunny   = new Client($eventLoop, $options);
        $bunny
            ->connect()
            ->then(function (Client $client) {
                return $client->channel();
            })
            ->then(function (Channel $channel) {
                return $this->prepareConsumer($channel);
            })
            ->then(function (array $all) use ($browser) {
                /** @var Channel $channel */
                $channel = $all[2];

                var_dump('before consume');

                $channel->consume(
                    function (Message $message, Channel $channel, Client $client) use ($browser) {
                        // TODO - handleMessage shouldn't need channel (publishing will be via different channel),
                        // TODO - handleMessage should return promise and message should be acked/nacked here
                        return $this->handleMessage($message, $channel, $browser);
                    },
                    self::INPUT_QUEUE
                );
            })
            ->otherwise(function (Exception $e) use ($eventLoop) {
                var_dump('Cannot connect to rabbitmq.', $e->getMessage());
                $eventLoop->stop();

                $this->consecutiveWait();
                $this->startLoop();
            });
    }

    private function handleMessage(Message $message, Channel $channel, Browser $browser): PromiseInterface
    {

        var_dump("Message received " . $message->content);

        $info = [
            'connector' => 'salesforce',
            'type'      => 'known_number',
            'from'      => '',
            'to'        => '',
        ];

        $reply = function (array $data) use ($channel, $message) {

            $channel->queueDeclare('reply')
                ->then(function () use ($channel, $message, $data) {
                    return $channel->publish(
                        json_encode($data),
                        [
                            'correlation-id' => $message->getHeader('correlation-id'),
                            'type'           => 'batch_item',
                        ],
                        '',
                        'reply'
                    );
                })
                ->then(function () {
                    var_dump("batch_item published");
                });
        };

        $connManager = new ConnectorManager();

        return $connManager->run($browser, $reply)->then(function () use ($channel, $message) {
            // todo - refactor using separate publisher on separate channel
            // spunt
            $channel->queueDeclare('reply')
                ->then(function () use ($channel, $message) {
                    $channel->publish(
                        '',
                        [
                            'correlation-id' => $message->getHeader('correlation-id'),
                            'type'           => 'batch_total',
                        ],
                        '',
                        'reply'
                    );
                })
                ->then(function () use ($channel, $message) {
                    var_dump('spunt published');
                    $channel->ack($message);
                });
        });
    }

    private function connectorStrategy($data, $sendReply)
    {
        switch ($data['type']) {
            case 'known_number':
                // call first request
                return [
                    $this->fetchSalesForceData()->then($sendReply),
                    $this->fetchSalesForceData()->then($sendReply),
                ];
                break;
            case 'unknown_number':
                return [$this->fetchSalesForceData()->then($sendReply)];
                break;
            default:
                throw new Exception('dasdasd');
        }
    }

    private function fetchSalesForceData()
    {
        return $this->fetchData('GET', 'http://jsonplaceholder.typicode.com/posts')
            ->then(
                function (Response $response) {
                    return ['message' => json_decode($response->getBody()->getContents())];
                },
                function (Response $response) {
                    throw new Exception($response->getReasonPhrase());
                }
            );
    }

    private function prepareConsumer(Channel $channel): PromiseInterface
    {
        return all([
            $channel->queueDeclare(self::INPUT_QUEUE),
            $channel->qos(0, 5),
            $this->getChannelPromise($channel),
        ]);
    }

    private function preparePublisher(Channel $channel, string $outputQueue): PromiseInterface
    {
        return $channel->queueDeclare($outputQueue);
    }

    private function getChannelPromise(Channel $channel)
    {
        return new Promise(function ($resolve) use ($channel) {
            $resolve($channel);
        });
    }

}