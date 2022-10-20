<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\DataStorage;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\Document\DataStorageDocument;

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
     * @param DatabaseManagerLocator $dml
     */
    public function __construct(private readonly DatabaseManagerLocator $dml)
    {
    }

    /**
     * @param string      $id
     * @param string|null $application
     * @param string|null $user
     * @param int|null    $skip
     * @param int|null    $limit
     * @param bool|null   $toArray
     *
     * @return mixed[]|null
     */
    public function load(
        string $id,
        ?string $application = NULL,
        ?string $user = NULL,
        ?int $skip = NULL,
        ?int $limit = NULL,
        ?bool $toArray = FALSE,
    ): array|null
    {
        $query = ['processId' => $id];
        if ($application) {
            $query['application'] = $application;
        }
        if ($user) {
            $query['user'] = $user;
        }

        if ($toArray) {
            return array_map(
                static fn($item) => $item->toArray(),
                $this->getRepository()?->findBy($query, NULL, $limit, $skip) ?? [],
            );
        }

        return $this->getRepository()?->findBy($query, NULL, $limit, $skip);
    }

    /**
     * @param string      $id
     * @param mixed[]     $data
     * @param string|NULL $application
     * @param string|NULL $user
     *
     * @return void
     * @throws MongoDBException
     */
    public function store(string $id, array $data, ?string $application = NULL, ?string $user = NULL): void
    {
        foreach ($data as $item) {
            $dataStorageDocument = (new DataStorageDocument())
                ->setUser($user)
                ->setApplication($application)
                ->setProcessId($id)
                ->setData($item);
            $this->getRepository()?->getDocumentManager()->persist($dataStorageDocument);
        }

        $this->getRepository()?->getDocumentManager()->flush();
    }

    /**
     * @param string      $id
     * @param string|NULL $application
     * @param string|NULL $user
     *
     * @return void
     * @throws MongoDBException
     */
    public function remove(string $id, ?string $application = NULL, ?string $user = NULL): void
    {
        $queryBuilder = $this->getRepository()?->getDocumentManager()->createQueryBuilder();
        $queryBuilder?->remove(DataStorageDocument::class)
            ->field('processId')->equals($id)
            ->field('application')->equals($application)
            ->field('user')->equals($user)
            ->getQuery()
            ->execute();
    }

    /**
     * @return DocumentRepository<DataStorageDocument>|null
     */
    private function getRepository(): DocumentRepository|null
    {
        return $this->dml->getDm()?->getRepository(DataStorageDocument::class);
    }

}
