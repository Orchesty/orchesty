<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Repository;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\PipesPhpSdk\Database\Document\Category;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use LogicException;
use MongoDB\BSON\ObjectId;

/**
 * Class TopologyRepository
 *
 * @package         Hanaboso\PipesPhpSdk\Database\Repository
 *
 * @phpstan-extends DocumentRepository<Topology>
 */
final  class TopologyRepository extends DocumentRepository
{

    /**
     * @param string $name
     *
     * @return Topology[]
     * @throws MongoDBException
     */
    public function getRunnableTopologies(string $name): array
    {
        /** @var Iterator<Topology> $result */
        $result = $this->createQueryBuilder()
            ->field('name')->equals($name)
            ->field('enabled')->equals(TRUE)
            ->field('deleted')->equals(FALSE)
            ->field('visibility')->equals(TopologyStatusEnum::PUBLIC)
            ->getQuery()->execute();

        return $result->toArray();
    }

    /**
     * @return integer
     * @throws MongoDBException
     */
    public function getTotalCount(): int
    {
        /** @var int $result */
        $result = $this->createQueryBuilder()
            ->field('deleted')->equals(FALSE)
            ->count()
            ->getQuery()->execute();

        return $result;
    }

    /**
     * @param bool $enabled
     *
     * @return int
     * @throws MongoDBException
     */
    public function getCountByEnable(bool $enabled): int
    {
        /** @var int $result */
        $result = $this->createQueryBuilder()
            ->field('deleted')->equals(FALSE)
            ->field('enabled')->equals($enabled)
            ->field('visibility')->equals(TopologyStatusEnum::PUBLIC)
            ->count()
            ->getQuery()->execute();

        return $result;
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function getMaxVersion(string $name): int
    {
        /** @var Topology|null $result */
        $result = $this->createQueryBuilder()
            ->field('name')->equals($name)
            ->sort('version', 'DESC')
            ->limit(1)
            ->getQuery()->getSingleResult();

        if (!$result) {
            return 0;
        }

        return $result->getVersion();
    }

    /**
     * @param string $topologyName
     *
     * @return int
     * @throws MongoDBException
     */
    public function getTopologiesCountByName(string $topologyName): int
    {
        /** @var int $result */
        $result = $this->createQueryBuilder()
            ->field('name')->equals($topologyName)
            ->field('deleted')->equals(FALSE)
            ->count()
            ->getQuery()->execute();

        return $result;
    }

    /**
     * @return Topology[]
     * @throws MongoDBException
     */
    public function getTopologies(): array
    {
        /** @var Iterator<Topology> $topology */
        $topology = $this->createQueryBuilder()
            ->field('visibility')->equals(TopologyStatusEnum::PUBLIC)
            ->field('deleted')->equals(FALSE)
            ->sort('version')
            ->getQuery()->execute();
        /** @var Topology[] $results */
        $results = $topology->toArray();

        $res = [];
        foreach ($results as $result) {
            $res[$result->getName()] = $result;
            unset($result);
        }

        return $res;
    }

    /**
     * @return Topology[]
     * @throws MongoDBException
     */
    public function getPublicEnabledTopologies(): array
    {
        /** @var Iterator<Topology> $result */
        $result = $this->createQueryBuilder()
            ->field('visibility')->equals(TopologyStatusEnum::PUBLIC)
            ->field('enabled')->equals(TRUE)
            ->field('deleted')->equals(FALSE)
            ->getQuery()
            ->execute();

        return $result->toArray();
    }

    /**
     * @param Category $category
     *
     * @return Topology[]
     */
    public function getTopologiesByCategory(Category $category): array
    {
        return $this->findBy(['category' => $category->getId(), 'deleted' => FALSE]);
    }

    /**
     * @param string $id
     *
     * @return Topology
     * @throws Exception
     */
    public function getTopologyById(string $id): Topology
    {
        /** @var Topology|null $topology */
        $topology = $this->findOneBy(['id' => $id, 'deleted' => FALSE]);

        if (!$topology) {
            throw new LogicException(
                sprintf('Node with id is not found [%s]', $id),
            );
        }

        return $topology;
    }

    /**
     * @param string $topologyId
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getActiveTopologiesVersions(string $topologyId): array
    {
        /** @var Iterator<Topology> $result */
        $result = $this->createAggregationBuilder()
            ->match()
            ->field('deleted')->equals(FALSE)
            ->field('enabled')->equals(TRUE)
            ->group()
            ->field('id')->expression('$name')
            ->field('topology')->push('$$ROOT')
            ->field('ids')->push('$_id')
            ->match()
            ->field('ids')
            ->equals(new ObjectId($topologyId))
            ->unwind('$topology')
            ->project()
            ->excludeFields(['_id'])
            ->field('id')->expression('$topology._id')
            ->field('name')->expression('$topology.name')
            ->field('version')->expression('$topology.version')
            ->getAggregation()
            ->getIterator();

        return $result->toArray();
    }

}
