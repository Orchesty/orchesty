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
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Psr\Container\ContainerInterface;
use React\EventLoop\Factory;
use React\Promise\Promise;
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
    protected function fetchData(string $method, string $url): Promise {
    	return new Promise(function (callable $resolve, callable $reject) use ($method, $url) {
    		$guzzle = new GuzzleClient();
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
        $eventLoop = Factory::create();
        $options   = [
            'host'  => 'rabbitmq',
            'vhost' => '/',
            'user'  => 'guest',
            'pass'  => 'guest',
        ];

        $salesForce = $this->fetchData('GET', 'http://jsonplaceholder.typicode.com/posts')
			->then(
				function (Response $response) {
					return ['message' => json_decode($response->getBody()->getContents())];
				},
				function (Response $response) {
					throw new Exception($response->getReasonPhrase());
				}
			);

        $connectorStrategy = function ($data, $sendReply) use ($salesForce) {
            switch ($data['type']) {
                case 'known_number':
                    // call first request
                    return [$salesForce->then($sendReply), $salesForce->then($sendReply)];
                    break;
                case 'unknown_number':
                    return [$salesForce];
                    break;
                default:
                    throw new Exception('dasdasd');
            }
        };

        $bunny = new Client($eventLoop, $options);
        $bunny->connect()
            ->then(function (Client $client) {
                return $client->channel();
            })
			->then(function (Channel $channel) {
                return $channel
					->queueDeclare('queue_name')
					->then(function () use ($channel) {
                    	return $channel;
                	});
            })
            ->then(function (Channel $channel) {
                return $channel->qos(0, 5)
					->then(function () use ($channel) {
                    	return $channel;
                	});
            })
			->then(function (Channel $channel) use ($connectorStrategy) {
                $channel->consume(
                    function (Message $message, Channel $channel, Client $client) use ($connectorStrategy) {



                        $info = [
                            'connector' => 'salesforce',
                            'type'      => 'known_number',
                            'from'      => '',
                            'to'        => '',
                        ];

                        $reply = function (array $data) use ($channel, $message) {

                            $channel->queueDeclare('reply')
								->then(function () use ($channel, $message, $data) {
									$channel->publish(
										json_encode($data),
										[
											'correlation-id' => $message->getHeader('correlation-id'),
											'type'           => 'batch_item',
										],
										'',
										'reply'
									);
                            	});
                        };

                        all([$connectorStrategy($info, $reply)])
                            ->then(function () use ($channel, $message) {
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
                                    	$channel->ack($message);
                                	});
                            })
							->cancel();
                    },
					'queue_name'
                );
            })
			->otherwise(function (Exception $e) {
				// Log bad connection
				var_dump("Error", $e->getMessage());
			});

        $eventLoop->run();

    }

}