<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Repository\LastSyncRepository;
use DateTime;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class LastSyncRepositoryTest
 *
 * @package Tests\Integration\AppBundle\Repository
 */
class LastSyncRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testGetLastSync(): void
    {
        $sync = new LastSync();
        $sync
            ->setUser('userId')
            ->setTopology('topologyId')
            ->setNode('nodeId')
            ->setTimestamp(new DateTime('now'));
        $sync2 = new LastSync();
        $sync2
            ->setUser('userId')
            ->setTopology('topologyId')
            ->setNode('nodeId')
            ->setTimestamp(new DateTime('-1 hours'));

        $this->dm->persist($sync);
        $this->dm->persist($sync2);
        $this->dm->flush($sync);
        $this->dm->flush($sync2);

        /** @var LastSyncRepository $repo */
        $repo = $this->dm->getRepository(LastSync::class);
        /** @var DateTime $res */
        $res = $repo->getLastSyncTime('userId', 'topologyId', 'nodeId');

        $this->assertInstanceOf(DateTime::class, $res);
        self::assertEquals($sync->getTimestamp(), $res);
    }

}