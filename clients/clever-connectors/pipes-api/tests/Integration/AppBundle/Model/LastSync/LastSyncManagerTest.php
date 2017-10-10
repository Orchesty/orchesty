<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\LastSync;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use DateTime;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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
        $this->manager = $this->container->get('manager.last_sync');
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

        $processDto = (new ProcessDto())->setHeaders(['node_id' => $node->getId()]);

        $this->dm->clear();
        $existingLastSync = $this->manager->getLastSync($processDto, $system, 'Node');
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

        $processDto = (new ProcessDto())->setHeaders(['node_id' => $node->getId()]);

        $this->dm->clear();
        $existingLastSync = $this->manager->getLastSync($processDto, $system, 'Node');
        $this->assertEquals(NULL, $existingLastSync->getTimestamp());
    }

}