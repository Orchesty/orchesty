<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntity;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Model\AuditEntityManager;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\ControllerUtils;

/**
 * Class AuditEntityHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Handler
 */
final class AuditEntityHandler
{

    /**
     * AuditEntityHandler constructor.
     *
     * @param AuditEntityManager $manager
     */
    public function __construct(private AuditEntityManager $manager)
    {
    }

    /**
     * @return mixed[]
     */
    public function getAll(): array
    {
        $entities = $this->manager->getAll();

        return [
            'filter' => [],
            'items'  => array_map(static fn(AuditEntity $entity): array => $entity->toArray(), $entities),
            'paging' => [
                'itemsPerPage' => 50,
                'lastPage'     => 1,
                'nextPage'     => 1,
                'page'         => 1,
                'previousPage' => 1,
                'total'        => count($entities),
            ],
        ];
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     */
    public function getOne(string $id): array
    {
        return $this->get($id)->toArray();
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws PipesFrameworkException
     * @throws MongoDBException
     */
    public function create(array $data): array
    {
        ControllerUtils::checkParameters([AuditEntity::KEY, AuditEntity::NAME, AuditEntity::FIELDS], $data);

        return $this->manager->create($data)->toArray();
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     * @throws MongoDBException
     */
    public function update(string $id, array $data): array
    {
        return $this->manager->update($this->get($id), $data)->toArray();
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     * @throws MongoDBException
     */
    public function delete(string $id): array
    {
        return $this->manager->delete($this->get($id))->toArray();
    }

    /**
     * @param string $id
     *
     * @return AuditEntity
     * @throws DocumentNotFoundException
     */
    private function get(string $id): AuditEntity
    {
        return $this->manager->getOne($id);
    }

}
