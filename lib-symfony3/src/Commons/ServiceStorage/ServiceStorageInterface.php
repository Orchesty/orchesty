<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 17:47
 */

namespace Hanaboso\PipesFramework\Commons\ServiceStorage;

use Hanaboso\PipesFramework\Commons\BaseService\BaseServiceInterface;
use stdClass;

/**
 * Interface ServiceStorageInterface
 *
 * @package Hanaboso\PipesFramework\Commons\ServiceStorage
 */
interface ServiceStorageInterface
{

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param null|object          $data
     *
     * @return bool
     */
    public function setObject(BaseServiceInterface $service, string $key, ?object $data): bool;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param array|null           $data
     *
     * @return bool
     */
    public function setArray(BaseServiceInterface $service, string $key, ?array $data): bool;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param null|string          $data
     *
     * @return bool
     */
    public function setString(BaseServiceInterface $service, string $key, ?string $data): bool;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param int|null             $data
     *
     * @return bool
     */
    public function setInt(BaseServiceInterface $service, string $key, ?int $data): bool;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param float|null           $data
     *
     * @return bool
     */
    public function setFloat(BaseServiceInterface $service, string $key, ?float $data): bool;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param bool|null            $data
     *
     * @return bool
     */
    public function setBool(BaseServiceInterface $service, string $key, ?bool $data): bool;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param null|object          $default
     *
     * @return null|stdClass
     */
    public function getObject(BaseServiceInterface $service, string $key, ?object $default = NULL): ?stdClass;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param array|null           $default
     *
     * @return array|null
     */
    public function getArray(BaseServiceInterface $service, string $key, ?array $default = NULL): ?array;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param null|string          $default
     *
     * @return null|string
     */
    public function getString(BaseServiceInterface $service, string $key, ?string $default = NULL): ?string;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param int|null             $default
     *
     * @return int|null
     */
    public function getInt(BaseServiceInterface $service, string $key, ?int $default = NULL): ?int;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param float|null           $default
     *
     * @return float|null
     */
    public function getFloat(BaseServiceInterface $service, string $key, ?float $default = NULL): ?float;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param bool|null            $default
     *
     * @return bool|null
     */
    public function getBool(BaseServiceInterface $service, string $key, ?bool $default = NULL): ?bool;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     *
     * @return bool
     */
    public function clear(BaseServiceInterface $service, string $key): bool;

    /**
     * @param BaseServiceInterface $service
     *
     * @return bool
     */
    public function clearAll(BaseServiceInterface $service): bool;

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     *
     * @return bool
     */
    public function keyExists(BaseServiceInterface $service, string $key): bool;

    /**
     * @param BaseServiceInterface $service
     * @param string               $id
     *
     * @return stdClass
     */
    public function getFile(BaseServiceInterface $service, string $id): stdClass;

}