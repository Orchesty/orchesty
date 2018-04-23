<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/14/18
 * Time: 10:26 AM
 */

namespace Demo\Command;

use Clue\React\Buzz\Browser;
use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
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
     * @var string
     */
    protected static $defaultName = 'downloader:run';

    /**
     * @var TimerInterface
     */
    private $heartbeat;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Downloader start.');

        $loop = Factory::create();

        $this->connect($loop, $output);

        $loop->run();
    }

    /**
     * @param LoopInterface   $loop
     * @param OutputInterface $output
     */
    private function connect(LoopInterface $loop, OutputInterface $output): void
    {
        $connector = new Connector($loop);
        $browser   = new Browser($loop);

        $uri = 'wss://ws.pusherapp.com/app/de504dc5763aeef9ff52?client=php-ratchet&version=0.0.1&protocol=5';

        $connector($uri)
            ->then(function (WebSocket $ws) use ($loop, $output, $uri, $browser): void {

                $this->heartbeat = $loop->addPeriodicTimer(5, function () use ($ws): void {
                    $ws->send(json_encode([
                        'event' => 'pusher:ping', 'data' => [],
                    ]));
                });

                $ws->on('message', function (MessageInterface $json) use ($ws, $output, $uri, $browser): void {

                    $json = (string) $json;

                    $data = json_decode($json, TRUE);

                    if (!array_key_exists('event', $data)) {
                        $output->writeln('Bad data - no event.');
                    }

                    switch ($data['event']) {
                        case 'pusher:connection_established':
                            $output->writeln(sprintf('Connection created: %s', $uri));
                            $channels = [
                                'order_book', // btcusd
                                'order_book_eurusd',
                                'order_book_btceur',
                                'order_book_xrpusd',
                                'order_book_xrpeur',
                                //'order_book_xrpbtc',
                                //'order_book_ltcusd',
                                //'order_book_ltceur',
                                //'order_book_ltcbtc',
                            ];
                            foreach ($channels as $channel) {
                                $ws->send(json_encode([
                                    'event' => 'pusher:subscribe', 'data' => ['channel' => $channel],
                                ]));
                            }
                            break;
                        case 'pusher_internal:subscription_succeeded':
                            $output->writeln(sprintf('Success subscribe to channel: %s', $data['channel']));
                            break;
                        case 'pusher:pong':
                            $output->writeln('Received pong event.');
                            break;
                        default:
                            if (array_key_exists('event', $data) && array_key_exists('channel', $data)) {
                                $output->writeln(sprintf(
                                    'Received event: %s for channel %s.',
                                    $data['event'],
                                    $data['channel']
                                ));
                                $this->sendData($json, $output, $browser);
                            } else {
                                $output->writeln(sprintf('Received unknown event: %s', json_encode($data)));
                            }
                    }

                });

                $ws->on('error', function (Exception $e) use ($ws, $loop, $output): void {
                    $output->writeln(sprintf('WS error: %s', $e->getMessage()));
                    $loop->cancelTimer($this->heartbeat);
                    $loop->addTimer(1, function () use ($ws, $loop, $output): void {
                        $this->reconnect($ws, $loop, $output);
                    });
                });

                $ws->on('close', function ($code, $reason) use ($ws, $output, $loop): void {
                    $output->writeln(sprintf('WS close with code %s: %s', $code, $reason));
                    $loop->cancelTimer($this->heartbeat);
                    $loop->addTimer(1, function () use ($ws, $loop, $output): void {
                        $this->reconnect($ws, $loop, $output);
                    });
                });

            })
            ->otherwise(function (Throwable $e) use ($output): void {
                $output->writeln(sprintf('Connection error: %s', $e->getMessage()));
            });
    }

    /**
     * @param WebSocket       $ws
     * @param LoopInterface   $loop
     * @param OutputInterface $output
     */
    private function reconnect(WebSocket $ws, LoopInterface $loop, OutputInterface $output): void
    {
        $output->writeln('Reconnecting.');
        $ws->close();
        $ws->removeAllListeners();
        $this->connect($loop, $output);
    }

    /**
     * @param string          $data
     * @param OutputInterface $output
     * @param Browser         $browser
     */
    private function sendData(string $data, OutputInterface $output, Browser $browser): void
    {
        $request = new Request(
            'POST',
            'http://frontend/topologies/stock-exchange/nodes/start/run',
            [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            $data
        );

        $browser->send($request)->then(function (ResponseInterface $response) use ($output): void {
            if ($response->getStatusCode() === 200) {
                $output->writeln('Send success request to pipes.');
            } else {
                $output->writeln(sprintf('Request Error with code %s: %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                ));
            }
        }, function (Exception $e) use ($output): void {
            $output->writeln(sprintf('Request Error: %s', $e->getMessage()));
        });
    }

}