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
use Hanaboso\PipesFramework\Commons\Metrics\Exception\SystemMetricException;
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
    private $ip = "";

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

        if (apcu_exists(self::APCU_IP . $collectorHost) &&
            apcu_exists(self::APCU_REFRESH . $collectorHost)
        ) {
            $this->ip            = apcu_fetch(self::APCU_IP . $collectorHost);
            $this->lastIPRefresh = apcu_fetch(self::APCU_REFRESH . $collectorHost);
        }

        // limit the ip addr hostname resolution
        putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:1');
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
        $ip     = $this->refreshIp();
        $socket = $this->getSocket();

        try {
            if ($ip === "") {
                throw new SystemMetricException(
                    sprintf('Could not sent udp packet. IP address for "%s" not resolved', $this->collectorHost)
                );
            }

            $sent = @socket_sendto($socket, $message, strlen($message), 0, $ip, $this->collectorPort);

            if ($sent === FALSE) {
                throw new SystemMetricException(
                    sprintf('Unable to send udp packet. Err: %s', socket_strerror(socket_last_error()))
                );
            }

            return TRUE;
        } catch (Exception $e) {
            $this->logger->error('Udp sender err:' . $e->getMessage(), ExceptionContextLoader::getContextForLogger($e));

            return FALSE;
        }
    }

    /**
     * Returns socket resource or null if socket cannot be created
     *
     * @return resource|null
     */
    private function getSocket()
    {
        if ($this->socket && socket_last_error($this->socket) != 0) {
            $this->socket = NULL;
        }

        if (!$this->socket) {
            $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ($socket === FALSE) {
                $this->logger->error(sprintf('Cannot create udp socket: %s', socket_strerror(socket_last_error())));
                $socket = NULL;
            }

            $this->socket = $socket;
        }

        return $this->socket;
    }

    /**
     * Returns the ip addr for the hostname
     * Does the periodical checks
     *
     * @return string
     */
    public function refreshIp(): string
    {
        if ($this->ip !== "" &&
            (new DateTime())->getTimestamp() <= $this->lastIPRefresh + self::REFRESH_INTERVAL
        ) {
            return $this->ip;
        }

        $this->ip            = $this->getIp($this->collectorHost);
        $this->lastIPRefresh = (new DateTime())->getTimestamp();

        apcu_delete(self::APCU_IP . $this->collectorHost);
        apcu_delete(self::APCU_REFRESH . $this->collectorHost);

        apcu_add(self::APCU_IP . $this->collectorHost, $this->ip);
        apcu_add(self::APCU_REFRESH . $this->collectorHost, $this->lastIPRefresh);

        return $this->ip;
    }

    /**
     * Returns the ip for the given hostname or returns empty string
     *
     * @param string $host
     *
     * @return string
     */
    private function getIp(string $host): string
    {
        $ip = gethostbyname($host);
        if ($ip !== $host) {
            return $ip;
        }

        return "";
    }

}
