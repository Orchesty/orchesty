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

    private const APCU_IP      = 'metrics_collector_ip';
    private const APCU_REFRESH = 'metrics_collector_refresh';

    private const REFRESH_INTERVAL = 60;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $ip;

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
    private $lastIPRefresh;

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
    public function __construct(string $collectorHost, int $collectorPort = 8089)
    {
        $this->collectorHost = $collectorHost;
        $this->collectorPort = $collectorPort;
        $this->logger        = new NullLogger();

        if (apcu_exists(self::APCU_IP) && apcu_exists(self::APCU_REFRESH)) {
            $this->ip = apcu_fetch(self::APCU_IP);
            $this->lastIPRefresh = apcu_fetch(self::APCU_REFRESH);
        } else {
            $this->refreshCollectorIp();
        }

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
    private function socketSendTo(string $message): bool
    {
        if (!$this->ip || (new DateTime())->getTimestamp() > $this->lastIPRefresh + self::REFRESH_INTERVAL) {
            $this->refreshCollectorIp();
        }

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
        $this->ip            = gethostbyname($this->collectorHost);
        $this->lastIPRefresh = (new DateTime())->getTimestamp();

        apcu_delete(self::APCU_IP);
        apcu_delete(self::APCU_REFRESH);

        apcu_add(self::APCU_IP, $this->ip);
        apcu_add(self::APCU_REFRESH, $this->lastIPRefresh);

        return $this->ip;
    }

}