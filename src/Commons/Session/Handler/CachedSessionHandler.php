<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Session\Handler;

use DateTime;
use SessionHandlerInterface;

/**
 * Class CachedSessionHandler
 *
 * @package Hanaboso\PipesFramework\Commons\Session\Handler
 */
class CachedSessionHandler implements SessionHandlerInterface
{

    private const APCU_DELIMITER = '::';
    private const APCU_DATA_KEY  = 'session-data';
    private const APCU_TIME_KEY  = 'session-time';
    private const APCU_TIMEOUT   = 5;

    /**
     * @var SessionHandlerInterface
     */
    private $handler;

    /**
     * @var int
     */
    private $timeout;

    /**
     * CachedRedisSessionHandler constructor.
     *
     * @param SessionHandlerInterface $handler
     */
    public function __construct(SessionHandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->timeout = self::APCU_TIMEOUT;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    public function read($session_id): string
    {
        $dataKey = self::APCU_DATA_KEY . self::APCU_DELIMITER . $session_id;
        $timeKey = self::APCU_TIME_KEY . self::APCU_DELIMITER . $session_id;

        // return cached value if found in cache and is not too old
        if (count(apcu_exists([$dataKey, $timeKey])) === 2 &&
            (new DateTime())->getTimestamp() < (int) apcu_fetch($timeKey) + $this->timeout
        ) {
            return (string) apcu_fetch($dataKey);
        }

        $data = $this->handler->read($session_id);
        $this->updateCache($session_id, $data);

        return $data;
    }

    /**
     * @param string $session_id
     * @param string $session_data
     *
     * @return bool
     */
    public function write($session_id, $session_data): bool
    {
        $this->updateCache($session_id, $session_data);

        return $this->handler->write($session_id, $session_data);
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function destroy($session_id): bool
    {
        apcu_delete(self::APCU_DATA_KEY . self::APCU_DELIMITER . $session_id);
        apcu_delete(self::APCU_TIME_KEY . self::APCU_DELIMITER . $session_id);

        return $this->handler->destroy($session_id);
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return $this->handler->close();
    }

    /**
     * @param int $maxlifetime
     *
     * @return bool
     */
    public function gc($maxlifetime): bool
    {
        return $this->handler->gc($maxlifetime);
    }

    /**
     * @param string $save_path
     * @param string $name
     *
     * @return bool
     */
    public function open($save_path, $name): bool
    {
        return $this->handler->open($save_path, $name);
    }

    /**
     * @param string $session_id
     * @param string $session_data
     */
    private function updateCache(string $session_id, string $session_data): void
    {
        apcu_store(self::APCU_DATA_KEY . self::APCU_DELIMITER . $session_id, $session_data);
        apcu_store(self::APCU_TIME_KEY . self::APCU_DELIMITER . $session_id, (new DateTime())->getTimestamp());
    }

}
