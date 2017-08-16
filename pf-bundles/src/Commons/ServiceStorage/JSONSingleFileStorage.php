<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 11:09
 */

namespace Hanaboso\PipesFramework\Commons\ServiceStorage;

use Exception;
use Hanaboso\PipesFramework\Commons\BaseService\BaseServiceInterface;
use stdClass;

/**
 * Class JSONSingleFileStorage
 *
 * @package Hanaboso\PipesFramework\Commons\ServiceStorage
 */
class JSONSingleFileStorage implements ServiceStorageInterface
{

    /**
     * @var string|null
     */
    private $filename;

    /**
     * @var bool
     */
    private $loaded = FALSE;

    /**
     * @var
     */
    private $storageData;

    /**
     * @var bool
     */
    private $pretty;

    /**
     * JSONSingleFileStorage constructor.
     *
     * @param string|NULL $filename
     * @param bool        $pretty
     */
    public function __construct(?string $filename = NULL, ?bool $pretty = FALSE)
    {
        $this->filename = $filename;
        $this->pretty   = $pretty;
    }

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     *
     * @return JSONSingleFileStorage
     */
    public function setFilename(string $filename): JSONSingleFileStorage
    {
        if ($this->filename != $filename) {
            $this->loaded      = FALSE;
            $this->storageData = NULL;
        }
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPretty(): bool
    {
        return $this->pretty;
    }

    /**
     * @param bool $pretty
     *
     * @return JSONSingleFileStorage
     */
    public function setPretty(bool $pretty): JSONSingleFileStorage
    {
        if ($this->pretty != $pretty) {
            $this->pretty = $pretty;
            $this->save();
        }

        return $this;
    }

    /**
     * @throws ServiceStorageException
     */
    protected function checkAndLoad(): void
    {
        if ($this->filename) {
            if (!$this->loaded) {
                if (file_exists($this->filename)) {
                    try {
                        $this->storageData = json_decode(file_get_contents($this->filename), TRUE);
                    } catch (Exception $e) {
                        throw new ServiceStorageException($e->getMessage(), ServiceStorageException::FAILED_LOAD_DATA);
                    }
                } else {
                    $this->storageData = [];
                }
                $this->loaded = TRUE;
            }
        } else {
            throw new ServiceStorageException('Filename is not set.',
                ServiceStorageException::MISSING_OR_INVALID_CONFIGURATION);
        }
    }

    /**
     * @throws ServiceStorageException
     */
    protected function save(): void
    {
        if ($this->filename) {
            if ($this->loaded) {
                try {
                    file_put_contents($this->filename,
                        json_encode($this->storageData, $this->pretty ? JSON_PRETTY_PRINT : 0));
                } catch (Exception $e) {
                    throw new ServiceStorageException($e->getMessage(), ServiceStorageException::FAILED_SAVE_DATA);
                }
            } else {
                throw new ServiceStorageException('Data must be loaded before save.',
                    ServiceStorageException::INVALID_STATE);
            }
        } else {
            throw new ServiceStorageException('Filename is not set.',
                ServiceStorageException::MISSING_OR_INVALID_CONFIGURATION);
        }
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param mixed                $data
     *
     * @return bool
     */
    protected function setData(BaseServiceInterface $service, string $key, $data): bool
    {
        $this->checkAndLoad();
        $this->storageData[$service->getId()][$key] = $data;
        $this->save();

        return TRUE;
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     *
     * @return mixed
     */
    protected function getData(BaseServiceInterface $service, string $key)
    {
        return $this->storageData[$service->getId()][$key];
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param null|object          $data
     *
     * @return bool
     */
    public function setObject(BaseServiceInterface $service, string $key, ?object $data): bool
    {
        return $this->setData($service, $key, $data);
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param array|null           $data
     *
     * @return bool
     */
    public function setArray(BaseServiceInterface $service, string $key, ?array $data): bool
    {
        return $this->setData($service, $key, $data);
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param null|string          $data
     *
     * @return bool
     */
    public function setString(BaseServiceInterface $service, string $key, ?string $data): bool
    {
        return $this->setData($service, $key, $data);
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param int|null             $data
     *
     * @return bool
     */
    public function setInt(BaseServiceInterface $service, string $key, ?int $data): bool
    {
        return $this->setData($service, $key, $data);
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param float|null           $data
     *
     * @return bool
     */
    public function setFloat(BaseServiceInterface $service, string $key, ?float $data): bool
    {
        return $this->setData($service, $key, $data);
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param bool|null            $data
     *
     * @return bool
     */
    public function setBool(BaseServiceInterface $service, string $key, ?bool $data): bool
    {
        return $this->setData($service, $key, $data);
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param null|object          $default
     *
     * @return null|stdClass
     * @throws ServiceStorageException
     */
    public function getObject(BaseServiceInterface $service, string $key, ?object $default = NULL): ?stdClass
    {
        if ($this->keyExists($service, $key)) {
            $data = $this->getData($service, $key);
            if ($data !== NULL) {
                if (is_array($data)) {
                    return (object) $data;
                } else {
                    throw new ServiceStorageException('Data not available as array.',
                        ServiceStorageException::DATA_TYPE_NOT_AVAILABLE);
                }
            } else {
                return NULL;
            }
        } else {
            return $default;
        }
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param array|null           $default
     *
     * @return array|null
     * @throws ServiceStorageException
     */
    public function getArray(BaseServiceInterface $service, string $key, ?array $default = NULL): ?array
    {
        if ($this->keyExists($service, $key)) {
            $data = $this->getData($service, $key);
            if ($data !== NULL) {
                if (is_array($data)) {
                    return $data;
                } else {
                    throw new ServiceStorageException('Data not available as array.',
                        ServiceStorageException::DATA_TYPE_NOT_AVAILABLE);
                }
            } else {
                return NULL;
            }
        } else {
            return $default;
        }
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param null|string          $default
     *
     * @return null|string
     */
    public function getString(BaseServiceInterface $service, string $key, ?string $default = NULL): ?string
    {
        if ($this->keyExists($service, $key)) {
            $data = $this->getData($service, $key);

            return $data !== NULL ? (string) $data : $data;
        } else {
            return $default;
        }
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param int|null             $default
     *
     * @return int|null
     */
    public function getInt(BaseServiceInterface $service, string $key, ?int $default = NULL): ?int
    {
        if ($this->keyExists($service, $key)) {
            $data = $this->getData($service, $key);

            return $data !== NULL ? (int) $data : $data;
        } else {
            return $default;
        }
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param float|null           $default
     *
     * @return float|null
     */
    public function getFloat(BaseServiceInterface $service, string $key, ?float $default = NULL): ?float
    {
        if ($this->keyExists($service, $key)) {
            $data = $this->getData($service, $key);

            return $data !== NULL ? (float) $data : $data;
        } else {
            return $default;
        }
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     * @param bool|null            $default
     *
     * @return bool|null
     */
    public function getBool(BaseServiceInterface $service, string $key, ?bool $default = NULL): ?bool
    {
        if ($this->keyExists($service, $key)) {
            $data = $this->getData($service, $key);

            return $data !== NULL ? (bool) $data : $data;
        } else {
            return $default;
        }
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     *
     * @return bool
     */
    public function clear(BaseServiceInterface $service, string $key): bool
    {
        $this->checkAndLoad();
        if ($this->keyExists($service, $key)) {
            unset($this->storageData[$service->getId()][$key]);
            $this->save();

            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * @param BaseServiceInterface $service
     *
     * @return bool
     */
    public function clearAll(BaseServiceInterface $service): bool
    {
        $this->checkAndLoad();
        unset($this->storageData[$service->getId()]);
        $this->save();

        return TRUE;
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $key
     *
     * @return bool
     */
    public function keyExists(BaseServiceInterface $service, string $key): bool
    {
        $this->checkAndLoad();
        $arr = $this->storageData[$service->getId()] ?? NULL;

        return is_array($arr) && array_key_exists($key, $arr);
    }

    /**
     * @param BaseServiceInterface $service
     * @param string               $id
     *
     * @return stdClass
     */
    public function getFile(BaseServiceInterface $service, string $id): stdClass
    {
        // TODO: Implement properly later
        $class       = new stdClass();
        $class->path = $id;

        return $class;
    }

}