<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\LastSync;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class LastSyncRepository
 */
class LastSyncRepository extends DocumentRepository
{

    /**
     * @param string $userId
     * @param string $topologyName
     * @param string $nodeName
     *
     * @return LastSync|null
     */
    public function getLastSyncTime(string $userId, string $topologyName, string $nodeName): ?LastSync
    {
        /** @var LastSync $res */
        $res = $this->createQueryBuilder()
            ->select('timestamp')
            ->field('user')->equals($userId)
            ->field('topologyName')->equals($topologyName)
            ->field('nodeName')->equals($nodeName)
            ->sort('timestamp', 'DESC')
            ->getQuery()
            ->getSingleResult();

        return $res ?? NULL;
    }

}