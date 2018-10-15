<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\LastSync;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use DateTime;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class LastSyncManagerTest
 *
 * @package Tests\Integration\AppBundle\Model\LastSync
 */
final class LastSyncManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var LastSyncManager
     */
    private $manager;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->ownContainer->get('cc.last_sync.manager');
    }

    /**
     *
     */
    public function testGetExistingLastSync(): void
    {
        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $system = (new SystemInstall())
            ->setUser('User')
            ->setToken('Token')
            ->setSystem('System');
        $this->persistAndFlush($system);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $lastSync = (new LastSync())
            ->setUser('User')
            ->setTopologyName('Topology')
            ->setNodeName('Node')
            ->setTimestamp(new DateTime('today midnight'));
        $this->persistAndFlush($lastSync);

        $arr = [
            CMHeaders::createKey(CMHeaders::TOPOLOGY_NAME) => $topology->getName(),
            CMHeaders::createKey(CMHeaders::NODE_NAME)     => 'Node',
        ];

        $this->dm->clear();
        $existingLastSync = $this->manager->getLastSync($system, $arr);
        $this->assertEquals($lastSync->getTimestamp(), $existingLastSync->getTimestamp());
    }

    /**
     *
     */
    public function testGetNonExistingLastSync(): void
    {
        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $system = (new SystemInstall())
            ->setUser('User')
            ->setToken('Token')
            ->setSystem('System');
        $this->persistAndFlush($system);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $arr = [
            CMHeaders::createKey(CMHeaders::TOPOLOGY_NAME) => $topology->getName(),
            CMHeaders::createKey(CMHeaders::NODE_NAME)     => 'Node',
        ];

        $this->dm->clear();
        $existingLastSync = $this->manager->getLastSync($system, $arr);
        $this->assertEquals(NULL, $existingLastSync->getTimestamp());
    }

    /**
     *
     */
    public function testUpdateLastSync(): void
    {
        $lastSync = (new LastSync())
            ->setUser('User')
            ->setTopologyName('Topology')
            ->setNodeName('Node');
        $this->persistAndFlush($lastSync);

        $this->dm->clear();
        /** @var LastSync $sync */
        $sync = $this->dm->getRepository(LastSync::class)->find($lastSync->getId());
        $sync->setNodeName('AA_AA');
        $this->manager->updateLastSync($sync);

        $this->dm->clear();
        /** @var LastSync $sync */
        $sync = $this->dm->getRepository(LastSync::class)->find($sync->getId());

        $this->assertSame('AA_AA', $sync->getNodeName());
    }

}