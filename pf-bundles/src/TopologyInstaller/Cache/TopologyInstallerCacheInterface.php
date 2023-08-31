<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller\Cache;

use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;

/**
 * Interface TopologyInstallerCacheInterface
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller\Cache
 */
interface TopologyInstallerCacheInterface
{

    /**
     * @param string           $key
     * @param CompareResultDto $dto
     */
    public function set(string $key, CompareResultDto $dto): void;

    /**
     * @param string $key
     *
     * @return CompareResultDto|null
     */
    public function get(string $key): ?CompareResultDto;

    /**
     * @param string $key
     */
    public function delete(string $key): void;

}
