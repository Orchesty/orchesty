<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Session\Handler;

use Predis\Client;
use SessionHandlerInterface;

/**
 * Class RedisSessionHandler
 *
 * @package Hanaboso\PipesFramework\Commons\Session\Handler
 */
final class RedisSessionHandler implements SessionHandlerInterface
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var int
     */
    private $lifeTime;

    /**
     * RedisSessionHandler constructor.
     *
     * @param Client $client
     * @param int    $lifeTime
     */
    public function __construct(Client $client, $lifeTime = 86400)
    {
        $this->client   = $client;
        $this->lifeTime = $lifeTime;
    }

    /**
     * @param string $savePath
     * @param string $name
     *
     * @return bool
     */
    public function open($savePath, $name): bool
    {
        return TRUE;
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    public function read($sessionId): string
    {
        return $this->client->get($sessionId) ?? '';
    }

    /**
     * @param string $sessionId
     * @param string $sessionData
     *
     * @return bool
     */
    public function write($sessionId, $sessionData): bool
    {
        $this->client->setex($sessionId, $this->lifeTime, $sessionData);

        return TRUE;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        $this->client->del([$sessionId]);

        return TRUE;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return TRUE;
    }

    /**
     * @param int $maxLifeTime
     *
     * @return bool
     */
    public function gc($maxLifeTime): bool
    {
        return TRUE;
    }

}