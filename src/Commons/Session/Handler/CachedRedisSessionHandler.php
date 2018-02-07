<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Session\Handler;

use DateTime;
use SessionHandlerInterface;

class CachedRedisSessionHandler implements SessionHandlerInterface
{

    private const APCU_DELIMITER = '::';
    private const APCU_DATA_KEY  = 'session-data';
    private const APCU_TIME_KEY  = 'session-time';
    private const APCU_TIMEOUT   = 5;

    /**
     * @var RedisSessionHandler
     */
    private $rsh;

    /**
     * CachedRedisSessionHandler constructor.
     *
     * @param RedisSessionHandler $rsh
     */
    public function __construct(RedisSessionHandler $rsh)
    {
        $this->rsh = $rsh;
    }

    /**
     * Read session data
     *
     * @link  http://php.net/manual/en/sessionhandlerinterface.read.php
     *
     * @param string $session_id The session id to read data for.
     *
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function read($session_id): string
    {
        $dataKey = self::APCU_DATA_KEY . self::APCU_DELIMITER . $session_id;
        $timeKey = self::APCU_TIME_KEY . self::APCU_DELIMITER . $session_id;

        if (count(apcu_exists([$dataKey, $timeKey])) !== 2) {
            $data = $this->rsh->read($session_id);
            $this->updateCache($session_id, $data);

            return $data;
        }

        $time = (int) apcu_fetch($timeKey);
        if ((new DateTime())->getTimestamp() >= $time + self::APCU_TIMEOUT) {
            $data = $this->rsh->read($session_id);
            $this->updateCache($session_id, $data);

            return $data;
        }

        $data = (string) apcu_fetch($dataKey);
        $this->updateCache($session_id, $data);

        return $data;
    }

    /**
     * Write session data
     *
     * @link  http://php.net/manual/en/sessionhandlerinterface.write.php
     *
     * @param string $session_id   The session id.
     * @param string $session_data <p>
     *                             The encoded session data. This data is the
     *                             result of the PHP internally encoding
     *                             the $_SESSION superglobal to a serialized
     *                             string and passing it as this parameter.
     *                             Please note sessions use an alternative serialization method.
     *                             </p>
     *
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function write($session_id, $session_data): bool
    {
        $this->updateCache($session_id, $session_data);

        return $this->rsh->write($session_id, $session_data);
    }

    /**
     * Destroy a session
     *
     * @link  http://php.net/manual/en/sessionhandlerinterface.destroy.php
     *
     * @param string $session_id The session ID being destroyed.
     *
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function destroy($session_id): bool
    {
        apcu_delete(self::APCU_DATA_KEY . self::APCU_DELIMITER . $session_id);
        apcu_delete(self::APCU_TIME_KEY . self::APCU_DELIMITER . $session_id);

        return $this->rsh->destroy($session_id);
    }

    /**
     * Close the session
     *
     * @link  http://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function close(): bool
    {
        return $this->rsh->close();
    }

    /**
     * Cleanup old sessions
     *
     * @link  http://php.net/manual/en/sessionhandlerinterface.gc.php
     *
     * @param int $maxlifetime <p>
     *                         Sessions that have not updated for
     *                         the last maxlifetime seconds will be removed.
     *                         </p>
     *
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function gc($maxlifetime): bool
    {
        return $this->rsh->gc($maxlifetime);
    }

    /**
     * Initialize session
     *
     * @link  http://php.net/manual/en/sessionhandlerinterface.open.php
     *
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $name      The session name.
     *
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($save_path, $name): bool
    {
        return $this->rsh->open($save_path, $name);
    }

    /**
     * @param string $session_id
     * @param string $session_data
     */
    private function updateCache(string $session_id, string $session_data): void
    {
        apcu_add(self::APCU_DATA_KEY . self::APCU_DELIMITER . $session_id, $session_data);
        apcu_add(self::APCU_TIME_KEY . self::APCU_DELIMITER . $session_id, (new DateTime())->getTimestamp());
    }

}
