<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 10/13/17
 * Time: 12:12 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventsManager;
use CleverConnectors\AppBundle\Model\DataLayout\LayoutManager;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\MapTemplate\MapManager;
use CleverConnectors\AppBundle\Model\SystemMetrics\SystemMetricsInterface;
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
use Hanaboso\PipesFramework\TopologyGenerator\Request\RequestHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class SystemManagerTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems
 */
final class SystemManagerTest extends TestCase
{

    /**
     * @var MockObject|RequestHandler
     */
    protected $requestHandler;

    /**
     * @var MockObject|DocumentManager
     */
    private $dm;

    /**
     * @var MockObject|SystemLoader
     */
    private $systemLoader;

    /**
     * @var MockObject|WebhookManager
     */
    private $webhookManager;

    /**
     * @var MockObject|StartingPoint
     */
    private $startingPoint;

    /**
     * @var MockObject|CMEventsManager
     */
    private $eventsManager;

    /**
     * @var MockObject|MapManager
     */
    private $mapManager;

    /**
     * @var MockObject|LayoutManager
     */
    private $layoutManager;

    /**
     * @var MockObject|SystemMetricsInterface
     */
    private $metrics;

    /**
     * @var MockObject|SystemLimitManager
     */
    private $systemLimitManager;

    /**
     *
     */
    public function setUp(): void
    {
        $this->dm             = $this->getClassMock(DocumentManager::class);
        $this->systemLoader   = $this->getClassMock(SystemLoader::class);
        $this->webhookManager = $this->getClassMock(WebhookManager::class);
        $this->startingPoint  = $this->getClassMock(StartingPoint::class);
        $this->requestHandler = $this->getClassMock(RequestHandler::class);
        $this->eventsManager  = $this->getClassMock(CMEventsManager::class);
        $this->mapManager     = $this->getClassMock(MapManager::class);
        $this->layoutManager  = $this->getClassMock(LayoutManager::class);
        $this->metrics        = $this->getClassMock(SystemMetricsInterface::class);
        $this->systemLimitManager = $this->getClassMock(SystemLimitManager::class);
        $this->startingPoint->method('runWithRequest');
    }

    /**
     * @covers SystemManager::synchronizeSubscriptions()
     */
    public function testSynchronizeSubscriptions(): void
    {
        $this->prepareDmMock(new Node());

        $manager = new SystemManager(
            $this->dm,
            $this->systemLoader,
            $this->webhookManager,
            $this->startingPoint,
            $this->requestHandler,
            $this->eventsManager,
            $this->mapManager,
            $this->layoutManager,
            $this->metrics,
            $this->systemLimitManager
        );

        $res = $manager->synchronizeSubscriptions('user', 'system');

        self::assertEquals(1, $res);
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

        $systemRepo = $this->getClassMock(SystemInstallRepository::class);
        $systemRepo
            ->method('findOneBy')
            ->willReturn((new SystemInstall())->setToken('123')->setSystem('testsys')->setUser('user'));

        $this->dm
            ->method('getRepository')
            ->willReturnOnConsecutiveCalls($systemRepo, $topoRepo, $nodeRepo);
    }

    /**
     * @param string $className
     *
     * @return MockObject|mixed
     */
    private function getClassMock($className)
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }

}
