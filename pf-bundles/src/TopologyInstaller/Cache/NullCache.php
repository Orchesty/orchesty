<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller\Cache;

use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;

/**
 * Class NullCache
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller\Cache
 */
final class NullCache implements TopologyInstallerCacheInterface
{

    /**
     * @param string           $key
     * @param CompareResultDto $dto
     */
    public function set(string $key, CompareResultDto $dto): void
    {
        $key;
        $dto;
    }

    /**
     * @param string $key
     *
     * @return CompareResultDto|null
     */
    public function get(string $key): ?CompareResultDto
    {
        $key;

        return NULL;
    }

    /**
     * @param string $key
     */
    public function delete(string $key): void
    {
        $key;
    }

}
