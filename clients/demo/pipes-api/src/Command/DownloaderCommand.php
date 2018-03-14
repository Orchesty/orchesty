<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/14/18
 * Time: 10:26 AM
 */

namespace Demo\Command;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class DownloaderCommand
 *
 * @package App\Command
 */
class DownloaderCommand extends Command
{

    /**
     * DownloaderCommand constructor.
     */
    public function __construct()
    {
        parent::__construct('downloader:run');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Downloader start.');

        $loop = Factory::create();

        $connector = new Connector($loop);

        $uri = 'wss://ws.pusherapp.com/app/de504dc5763aeef9ff52?client=php-ratchet&version=0.0.1&protocol=5';

        $connector($uri)
            ->then(function (WebSocket $ws) use ($output, $uri): void {

                $ws->on('message', function (string $json) use ($ws, $output, $uri): void {

                    $data = json_decode($json, TRUE);

                    if (!array_key_exists('event', $data)) {
                        $output->writeln('Bad data - no event.');
                    }

                    switch ($data['event']) {
                        case 'pusher:connection_established':
                            $output->writeln(sprintf('Connection created: %s', $uri));
                            $ws->send(json_encode([
                                'event' => 'pusher:subscribe', 'data' => ['channel' => 'order_book_eurusd'],
                            ]));
                            break;
                        case 'pusher_internal:subscription_succeeded':
                            $output->writeln(sprintf('Success subscribe to channel: %s', $data['channel']));
                            break;
                        default:
                            $output->writeln(sprintf(
                                'Received event: %s for channel %s.',
                                $data['event'],
                                $data['channel']
                            ));
                            $this->sendData($json, $output);
                    }

                });

                $ws->on('error', function (Exception $e) use ($output): void {
                    $output->writeln(sprintf('WS error: %s', $e->getMessage()));
                });

                $ws->on('close', function ($code, $reason) use ($output): void {
                    $output->writeln(sprintf('WS close with code %s: %s', $code, $reason));
                });

            })
            ->otherwise(function (Throwable $e) use ($output): void {
                $output->writeln(sprintf('Connection error: %s', $e->getMessage()));
            });

        $loop->run();
    }

    /**
     * @param string          $data
     * @param OutputInterface $output
     */
    private function sendData(string $data, OutputInterface $output): void
    {
        $request = new Request(
            'POST',
            'http://frontend/api/topologies/stock-exchange/nodes/null/run_by_name',
            [
                'Content-Type' => 'application/json',
            ],
            $data
        );

        $client = new Client();

        try {
            $response = $client->send($request);

            if ($response->getStatusCode() === 200) {
                $output->writeln('Send success request to pipes.');
            } else {
                $output->writeln(sprintf('Request Error with code %s: %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                ));
            }
        } catch (Throwable $e) {
            $output->writeln(sprintf('Request Error: %s', $e->getMessage()));
        }
    }

}