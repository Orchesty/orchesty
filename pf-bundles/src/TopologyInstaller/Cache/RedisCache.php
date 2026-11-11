<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller\Cache;

use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;
use Predis\Client;
use Predis\Connection\Parameters;

/**
 * Class RedisCache
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller\Cache
 */
final class RedisCache implements TopologyInstallerCacheInterface
{

    /**
     * RedisCache constructor.
     *
     * @param string $redisDsn
     */
    public function __construct(private string $redisDsn)
    {
    }

    /**
     * @param string           $key
     * @param CompareResultDto $dto
     */
    public function set(string $key, CompareResultDto $dto): void
    {
        $this->getRedisClient()->set($key, serialize($dto));
    }

    /**
     * @param string $key
     *
     * @return CompareResultDto|null
     */
    public function get(string $key): ?CompareResultDto
    {
        $record = $this->getRedisClient()->get($key);

        if (!$record) {
            return NULL;
        }

        /** @var CompareResultDto $dto */
        $dto = unserialize($record);

        return $dto;
    }

    /**
     * @param string $key
     */
    public function delete(string $key): void
    {
        $this->getRedisClient()->del([$key]);
    }

    /**
     * @return Client<mixed>
     */
    private function getRedisClient(): Client
    {
        return new Client(Parameters::create($this->redisDsn));
    }

}
