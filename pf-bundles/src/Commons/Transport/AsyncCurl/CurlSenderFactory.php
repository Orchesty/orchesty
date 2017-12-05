<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10.10.17
 * Time: 13:40
 */

namespace Hanaboso\PipesFramework\Commons\Transport\AsyncCurl;

use Clue\React\Buzz\Browser;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;
use React\Socket\SecureConnector;

/**
 * Class CurlFactory
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Async\Curl
 */
class CurlSenderFactory implements LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CurlFactory constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param LoopInterface $loop
     * @param array         $secret
     *
     * @return CurlSender
     */
    public function create(LoopInterface $loop, array $secret = []): CurlSender
    {
        $browser = new Browser($loop);

        if (isset($secret['ca']) && isset($secret['cert'])) {
            $context = [
                'verify_peer' => TRUE,
                'cafile'      => $secret['ca'],
                'local_cert'  => $secret['cert'],
            ];
            $browser = new Browser($loop, new SecureConnector(new Connector($loop), $loop, $context));
        }

        $curlSender = new CurlSender($browser);
        $curlSender->setLogger($this->logger);

        return $curlSender;
    }

}