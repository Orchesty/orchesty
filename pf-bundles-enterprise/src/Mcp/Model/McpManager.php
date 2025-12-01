<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyProgressRepository;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditData;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditDataField;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntity;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditDataRepository;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditEntityRepository;

/**
 * Class McpManager
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Model
 */
final class McpManager
{

    /**
     * @var ObjectRepository<AuditEntity>&AuditEntityRepository
     */
    private ObjectRepository $auditEntityRepository;

    /**
     * @var ObjectRepository<AuditData>&AuditDataRepository
     */
    private ObjectRepository $auditDataRepository;

    /**
     * @var ObjectRepository<TopologyProgress>&TopologyProgressRepository
     */
    private ObjectRepository $topologyProgressRepository;

    /**
     * McpManager constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->auditEntityRepository      = $dm->getRepository(AuditEntity::class);
        $this->auditDataRepository        = $dm->getRepository(AuditData::class);
        $this->topologyProgressRepository = $dm->getRepository(TopologyProgress::class);
    }

    /**
     * @return mixed[]
     */
    public function getTopologiesEntitiesManifest(): array
    {
        /** @var AuditEntity[] $entities */
        $entities = $this->auditEntityRepository->findBy([]);

        return array_map(static function (AuditEntity $entity): array {
            $properties = [];

            foreach ($entity->getFields() as $field) {
                $properties[$field->getKey()] = [
                    'description' => $field->getName(),
                    'type' => 'string',
                ];
            }

            return [
                'id' => $entity->getKey(),
                'input_schema' => [
                    'minProperties' => 1,
                    'properties' => $properties,
                    'type' => 'object',
                ],
                'kind' => 'query',
                'output_schema' => [
                    'type' => 'object',
                ],
                'title' => $entity->getName(),
            ];
        }, $entities);
    }

    /**
     * @return mixed[]
     */
    public function getManifest(): array
    {
        return $this->getTopologiesEntitiesManifest();
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function run(array $data): array
    {
        $audit      = $data['audit'];
        $searchData = $data['data'];

        /** @var AuditEntity|null $auditEntity */
        $auditEntity = $this->auditEntityRepository->findOneBy([AuditEntity::KEY => $audit]);

        if (!$auditEntity) {
            return [];
        }

        $queryBuilder = $this
            ->auditDataRepository
            ->createQueryBuilder()
            ->field(AuditData::ENTITY)
            ->equals($auditEntity->getId());

        foreach ($searchData as $key => $value) {
            $queryBuilder
                ->field(AuditData::FIELDS)
                ->elemMatch(
                    $queryBuilder
                        ->expr()
                        ->field(AuditDataField::KEY)
                        ->equals($key)
                        ->field(AuditDataField::VALUE)
                        ->equals($value),
                );
        }

        $auditDataIds = array_map(
            static fn(AuditData $auditData): string => $auditData->getId(),
            $queryBuilder->getQuery()->execute()->toArray(), /** @phpstan-ignore-line */
        );

        return array_map(
            static fn(TopologyProgress $progress): string => $progress->getId(),
            $this->topologyProgressRepository->findBy(['auditData' => ['$in' => $auditDataIds]]),
        );
    }

}
