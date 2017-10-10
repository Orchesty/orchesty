<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10.10.17
 * Time: 13:40
 */

namespace Hanaboso\PipesFramework\RabbitMq\Async\Curl;

use Clue\React\Buzz\Browser;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;

/**
 * Class CurlFactory
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Async\Curl
 */
class CurlFactory implements LoggerAwareInterface
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
     *
     * @return CurlSender
     */
    public function create(LoopInterface $loop): CurlSender
    {
        $curlSender = new CurlSender(new Browser($loop));
        $curlSender->setLogger($this->logger);

        return $curlSender;
    }

}