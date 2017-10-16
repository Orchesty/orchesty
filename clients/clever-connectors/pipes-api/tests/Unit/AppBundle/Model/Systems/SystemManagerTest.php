<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 10/13/17
 * Time: 12:12 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Model\Webhook\WebhookManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class SystemManagerTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems
 */
class SystemManagerTest extends TestCase
{

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|DocumentManager
     */
    private $dm;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|SystemLoader
     */
    private $systemLoader;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|WebhookManager
     */
    private $webhookManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|StartingPoint
     */
    private $startingPoint;

    /**
     *
     */
    public function setUp(): void
    {
        $this->dm             = $this->getClassMock(DocumentManager::class);
        $this->systemLoader   = $this->getClassMock(SystemLoader::class);
        $this->webhookManager = $this->getClassMock(WebhookManager::class);
        $this->startingPoint  = $this->getClassMock(StartingPoint::class);
        $this->startingPoint->method('runWithRequest');
    }

    /**
     * @covers SystemManager::synchronizeSubscriptions()
     */
    public function testSynchronizeSubscriptions(): void
    {
        $this->prepareDmMock(new Node());

        $manager = new SystemManager($this->dm, $this->systemLoader, $this->webhookManager, $this->startingPoint);
        $manager->synchronizeSubscriptions('user', 'system');
    }

    /**
     * @covers SystemManager::synchronizeSubscriptions()
     */
    public function testSynchronizeSubscriptionsNodeNotFound(): void
    {
        $this->prepareDmMock();

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::STARTING_NODE_NOT_FOUND);

        $manager = new SystemManager($this->dm, $this->systemLoader, $this->webhookManager, $this->startingPoint);
        $manager->synchronizeSubscriptions('user', 'system');
    }

    /**
     * @param Node|null $node
     */
    private function prepareDmMock(?Node $node = NULL): void
    {
        $topo = $this->getClassMock(Topology::class);
        $topo
            ->method('getId')
            ->willReturn('abc123');

        $topoRepo = $this->getClassMock(TopologyRepository::class);
        $topoRepo
            ->method('getRunnableTopologies')
            ->willReturn([$topo]);

        $nodeRepo = $this->getClassMock(NodeRepository::class);
        $nodeRepo
            ->method('findOneBy')
            ->willReturn($node);

        $this->dm
            ->method('getRepository')
            ->willReturnOnConsecutiveCalls($this->getClassMock(SystemInstallRepository::class), $topoRepo, $nodeRepo);
    }

    /**
     * @param string $className
     *
     * @return PHPUnit_Framework_MockObject_MockObject|mixed
     */
    private function getClassMock($className)
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }

}