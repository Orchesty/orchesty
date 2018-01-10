<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/21/17
 * Time: 1:09 PM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage\Driver;

use Hanaboso\PipesFramework\Commons\Enum\StorageTypeEnum;
use Hanaboso\PipesFramework\Commons\Exception\FileStorageException;

/**
 * Class FileStorageDriverLocator
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\Driver
 */
class FileStorageDriverLocator
{

    /**
     * @var FileStorageDriverInterface
     */
    private $persistent;

    /**
     * @var FileStorageDriverInterface
     */
    private $temporary;

    /**
     * @var FileStorageDriverInterface
     */
    private $public;

    /**
     * FIleStorageDriverLocator constructor.
     *
     * @param FileStorageDriverInterface $persistent
     * @param FileStorageDriverInterface $temporary
     * @param FileStorageDriverInterface $public
     */
    function __construct(
        FileStorageDriverInterface $persistent,
        FileStorageDriverInterface $temporary,
        FileStorageDriverInterface $public)
    {
        $this->persistent = $persistent;
        $this->temporary  = $temporary;
        $this->public     = $public;
    }

    /**
     * @param string $type
     *
     * @return FileStorageDriverInterface
     * @throws FileStorageException
     */
    public function get(string $type): FileStorageDriverInterface
    {
        switch ($type) {
            case StorageTypeEnum::PERSISTENT:
                return $this->persistent;
            case StorageTypeEnum::TEMPORARY:
                return $this->temporary;
            case StorageTypeEnum::PUBLIC:
                return $this->public;
            default:
                throw new FileStorageException(
                    sprintf('Given storage type [%s] is not a valid option.', $type),
                    FileStorageException::INVALID_STORAGE_TYPE
                );
        }
    }

}