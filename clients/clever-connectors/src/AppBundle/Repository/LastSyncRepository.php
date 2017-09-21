<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\LastSync;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class LastSyncRepository
 */
class LastSyncRepository extends DocumentRepository
{

    /**
     * @param string $userId
     * @param string $topologyId
     * @param string $nodeId
     *
     * @return DateTime
     */
    public function getLastSyncTime(string $userId, string $topologyId, string $nodeId): DateTime
    {
        /** @var LastSync $res */
        $res = $this->createQueryBuilder()
            ->select('timestamp')
            ->field('user')->equals($userId)
            ->field('topology')->equals($topologyId)
            ->field('node')->equals($nodeId)
            ->sort('timestamp', 'DESC')
            ->getQuery()
            ->getSingleResult();

        return $res->getTimestamp();
    }

}