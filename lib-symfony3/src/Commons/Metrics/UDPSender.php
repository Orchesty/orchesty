<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 8/8/17
 * Time: 1:19 PM
 */

namespace Hanaboso\PipesFramework\Commons\Metrics;

use DateTime;
use Exception;
use Hanaboso\PipesFramework\Commons\Utils\ExceptionContextLoader;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class UDPSender
 */
class UDPSender implements LoggerAwareInterface
{

    private const REFRESH_INTERVAL = 60;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $ip = '';

    /**
     * @var string
     */
    private $collectorHost;

    /**
     * @var int
     */
    private $collectorPort;

    /**
     * @var int
     */
    private $lastRefresh;

    /**
     * @var resource|null
     */
    private $socket = NULL;

    /**
     * UDPSender constructor.
     *
     * @param string $collectorHost
     * @param int    $collectorPort
     */
    public function __construct(string $collectorHost = 'metrics', int $collectorPort = 8089)
    {
        $this->collectorHost = $collectorHost;
        $this->collectorPort = $collectorPort;
        $this->logger        = new NullLogger();

        $this->refreshCollectorIp();
        $this->socketCreate();
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return UDPSender
     */
    public function setLogger(LoggerInterface $logger): UDPSender
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function send(string $message): bool
    {
        if ((new DateTime())->getTimestamp() > $this->lastRefresh + self::REFRESH_INTERVAL) {
            $this->refreshCollectorIp();
        }

        // Recreate socket if needed
        if (socket_last_error($this->socket) != 0) {
            $this->socketCreate();
        }

        try {
            return $this->socketSendTo($message);
        } catch (Exception $e) {
            $this->logger->error(
                'Metrics sender: ' . $e->getMessage(),
                ExceptionContextLoader::getContextForLogger($e)
            );

            return FALSE;
        }
    }

    /**
     * @return resource|null
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function socketSendTo(string $message): bool
    {
        $result = @socket_sendto($this->socket, $message, strlen($message), 0, $this->ip, $this->collectorPort);

        if ($result === FALSE) {
            $this->logger->error(
                sprintf('socket_sendto() failed: %s', socket_strerror(socket_last_error()))
            );

            return FALSE;
        }

        return TRUE;
    }

    /**
     *
     */
    private function socketCreate(): void
    {
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === FALSE) {
            $this->logger->error(
                sprintf('socket_create() failed: %s', socket_strerror(socket_last_error()))
            );
            $socket = NULL;
        }

        $this->socket = $socket;
    }

    /**
     * Updates host ip to actual value
     *
     * @return string
     */
    private function refreshCollectorIp(): string
    {
        $this->ip          = gethostbyname($this->collectorHost);
        $this->lastRefresh = (new DateTime())->getTimestamp();

        return $this->ip;
    }

}