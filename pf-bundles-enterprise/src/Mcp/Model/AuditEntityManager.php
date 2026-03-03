<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntity;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntityField;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditEntityRepository;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class AuditEntityManager
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Model
 */
final class AuditEntityManager
{

    /**
     * @var ObjectRepository<AuditEntity>&AuditEntityRepository
     */
    private ObjectRepository $repository;

    /**
     * AuditEntityManager constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(private DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(AuditEntity::class);
    }

    /**
     * @return AuditEntity[]
     */
    public function getAll(): array
    {
        return $this->repository->findBy([]);
    }

    /**
     * @param string $id
     *
     * @return AuditEntity
     * @throws DocumentNotFoundException
     */
    public function getOne(string $id): AuditEntity
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw new DocumentNotFoundException(sprintf("Document AuditEntity with key '%s' not found!", $id));
        }

        return $entity;
    }

    /**
     * @param mixed[] $data
     *
     * @return AuditEntity
     * @throws MongoDBException
     * @throws PipesFrameworkException
     */
    public function create(array $data): AuditEntity
    {
        $entity = new AuditEntity();
        $entity->setKey($data[AuditEntity::KEY]);
        $entity->setName($data[AuditEntity::NAME]);

        $this->setEntityFields($entity, $data);

        $this->dm->persist($entity);
        $this->dm->flush();

        return $entity;
    }

    /**
     * @param AuditEntity $entity
     * @param mixed[]     $data
     *
     * @return AuditEntity
     * @throws MongoDBException
     */
    public function update(AuditEntity $entity, array $data): AuditEntity
    {
        if (isset($data[AuditEntity::KEY])) {
            $entity->setKey($data[AuditEntity::KEY]);
        }

        if (isset($data[AuditEntity::NAME])) {
            $entity->setName($data[AuditEntity::NAME]);
        }

        $this->setEntityFields($entity, $data);

        $this->dm->flush();

        return $entity;
    }

    /**
     * @param AuditEntity $entity
     *
     * @return AuditEntity
     * @throws MongoDBException
     */
    public function delete(AuditEntity $entity): AuditEntity
    {
        $this->dm->remove($entity);
        $this->dm->flush();

        return $entity;
    }

    /**
     * @param AuditEntity $entity
     * @param mixed[]     $data
     */
    private function setEntityFields(AuditEntity $entity, array $data): void
    {
        if (isset($data[AuditEntity::FIELDS]) && is_array($data[AuditEntity::FIELDS])) {
            $fields = [];

            foreach ($data[AuditEntity::FIELDS] as $fieldData) {
                if (isset($fieldData[AuditEntityField::KEY]) && isset($fieldData[AuditEntityField::NAME])) {
                    $field = new AuditEntityField();
                    $field->setKey($fieldData[AuditEntityField::KEY]);
                    $field->setName($fieldData[AuditEntityField::NAME]);
                    $fields[] = $field;
                }
            }

            $entity->setFields(new ArrayCollection($fields));
        }
    }

}
