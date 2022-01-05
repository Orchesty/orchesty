<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Repository\SdkRepository;

/**
 * Class SdkManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class SdkManager
{

    /**
     * @var ObjectRepository<Sdk>&SdkRepository
     */
    private SdkRepository $repository;

    /**
     * SdkManager constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(private DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(Sdk::class);
    }

    /**
     * @return Sdk[]
     */
    public function getAll(): array
    {
        /** @var Sdk[] $sdks */
        $sdks = $this->repository->findAll();

        return $sdks;
    }

    /**
     * @param string $id
     *
     * @return Sdk
     * @throws DocumentNotFoundException
     */
    public function getOne(string $id): Sdk
    {
        /** @var Sdk|null $sdk */
        $sdk = $this->repository->findOneBy([Sdk::ID => $id]);

        if (!$sdk) {
            throw new DocumentNotFoundException(sprintf("Document Sdk with key '%s' not found!", $id));
        }

        return $sdk;
    }

    /**
     * @param mixed[] $data
     *
     * @return Sdk
     * @throws MongoDBException
     */
    public function create(array $data): Sdk
    {
        $sdk = (new Sdk())
            ->setName($data[Sdk::NAME])
            ->setHeaders($data[Sdk::HEADERS] ?? [])
            ->setUrl($data[Sdk::URL]);

        $this->dm->persist($sdk);
        $this->dm->flush();

        return $sdk;
    }

    /**
     * @param Sdk     $sdk
     * @param mixed[] $data
     *
     * @return Sdk
     * @throws MongoDBException
     */
    public function update(Sdk $sdk, array $data): Sdk
    {
        if (isset($data[Sdk::NAME])) {
            $sdk->setName($data[Sdk::NAME]);
        }

        if (isset($data[Sdk::URL])) {
            $sdk->setUrl($data[Sdk::URL]);
        }

        $sdk->setHeaders($data[Sdk::HEADERS] ?? []);

        $this->dm->flush();

        return $sdk;
    }

    /**
     * @param Sdk $sdk
     *
     * @return Sdk
     * @throws MongoDBException
     */
    public function delete(Sdk $sdk): Sdk
    {
        $this->dm->remove($sdk);
        $this->dm->flush();

        return $sdk;
    }

}
