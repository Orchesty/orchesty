<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Topology;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class TopologyRepository
 *
 * @package Hanaboso\PipesFramework\Commons\Topology
 */
class TopologyRepository extends DocumentRepository
{

    /**
     * @return integer
     */
    public function getTotalCount(): int
    {
        return $this->createQueryBuilder()->count()->getQuery()->execute();
    }

}