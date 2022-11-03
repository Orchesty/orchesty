<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\DataStorage;

use Exception;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\Document\DataStorageDocument;
use Hanaboso\PipesPhpSdk\Storage\File\FileSystem;

/**
 * Class DataStorageManager
 *
 * @package Hanaboso\PipesPhpSdk\Storage\DataStorage
 */
final class DataStorageManager
{

    /**
     * DataStorageManager constructor.
     *
     * @param FileSystem $fileSystem
     */
    public function __construct(private readonly FileSystem $fileSystem)
    {
    }

    /**
     * @param string      $id
     * @param string|null $application
     * @param string|null $user
     * @param int|null    $skip
     * @param int|null    $limit
     *
     * @return DataStorageDocument[]
     * @throws Exception
     */
    public function load(
        string $id,
        ?string $application = NULL,
        ?string $user = NULL,
        ?int $skip = NULL,
        ?int $limit = NULL,
    ): array
    {
        $findData = $this->fileSystem->read($id);

        $end      = ($skip ?? 0) + ($limit ?? 0);
        $filtered = $this->filterData($findData, TRUE, $application, $user);

        return array_slice($filtered, $skip ?? 0, $end ?: count($filtered));
    }

    /**
     * @param string      $id
     * @param mixed[]     $data
     * @param string|NULL $application
     * @param string|NULL $user
     *
     * @return void
     * @throws Exception
     */
    public function store(string $id, array $data, ?string $application = NULL, ?string $user = NULL): void
    {
        $entities = array_map(
            static fn($item) => (new DataStorageDocument())
            ->setUser($user)
            ->setApplication($application)
            ->setData($item),
            $data,
        );

        $dbData = $this->fileSystem->read($id);
        $this->fileSystem->write($id, array_merge($dbData, $entities));
    }

    /**
     * @param string      $id
     * @param string|NULL $application
     * @param string|NULL $user
     *
     * @return void
     * @throws Exception
     */
    public function remove(string $id, ?string $application = NULL, ?string $user = NULL): void
    {
        if (!$application && !$user) {
            $this->fileSystem->delete($id);
        } else {
            $data = $this->fileSystem->read($id);

            $data = $this->filterData($data, FALSE, $application, $user);
            $this->fileSystem->write($id, $data);
        }
    }

    /**
     * @param DataStorageDocument[] $data
     * @param bool|null             $contains
     * @param string|null           $application
     * @param string|null           $user
     *
     * @return DataStorageDocument[]
     */
    private function filterData(
        array $data,
        ?bool $contains = NULL,
        ?string $application = NULL,
        ?string $user = NULL,
    ): array {
        if ($application) {
            $data = array_filter($data, static fn($item) => ($item->getApplication() === $application) === $contains);
        }
        if ($user) {
            $data = array_filter($data, static fn($item) => ($item->getUser() === $user) === $contains);
        }

        return $data;
    }

}
