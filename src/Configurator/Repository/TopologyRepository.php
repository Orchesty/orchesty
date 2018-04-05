<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Configurator\Document\Topology;

/**
 * Class TopologyRepository
 *
 * @package Hanaboso\PipesFramework\Configurator\Repository
 */
class TopologyRepository extends DocumentRepository
{

    /**
     * @param string $name
     *
     * @return Topology[]
     */
    public function getRunnableTopologies(string $name): array
    {
        /** @var Cursor $result */
        $result = $this->createQueryBuilder()
            ->field('name')->equals($name)
            ->field('enabled')->equals(TRUE)
            ->field('visibility')->equals(TopologyStatusEnum::PUBLIC)
            ->getQuery()->execute();

        return $result->toArray(FALSE);
    }

    /**
     * @return integer
     */
    public function getTotalCount(): int
    {
        return $this->createQueryBuilder()->field('deleted')->equals(FALSE)->count()->getQuery()->execute();
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function getMaxVersion(string $name): int
    {
        /** @var Topology $result */
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
     */
    public function getTopologiesCountByName(string $topologyName): int
    {
        return $this->createQueryBuilder()
            ->field('name')->equals($topologyName)
            ->field('deleted')->equals(FALSE)
            ->count()
            ->getQuery()->execute();
    }

    /**
     * @return Topology[]
     */
    public function getTopologies(): array
    {
        /** @var Cursor $result */
        $result = $this->createQueryBuilder()
            ->field('visibility')
            ->equals(TopologyStatusEnum::PUBLIC)
            ->field('deleted')
            ->equals(FALSE)
            ->sort('version', 1)
            ->getQuery()->execute();
        /** @var Topology[] $results */
        $results = $result->toArray(FALSE);

        $res = [];
        foreach ($results as $result) {
            $res[$result->getName()] = $result;
            unset($result);
        }

        return $res;
    }

    /**
     * @return Topology[]
     */
    public function getPublicEnabledTopologies(): array
    {
        /** @var Cursor $result */
        $result = $this->createQueryBuilder()
            ->field('visibility')
            ->equals(TopologyStatusEnum::PUBLIC)
            ->field('enabled')
            ->equals(TRUE)
            ->field('deleted')
            ->equals(FALSE)
            ->getQuery()
            ->execute();

        return $result->toArray(TRUE);
    }

    /**
     * @param Category $category
     *
     * @return Topology[]
     */
    public function getTopologiesByCategory(Category $category): array
    {
        return $this->findBy(['category' => $category->getId()]);
    }

}