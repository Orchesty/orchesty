<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 3:46 PM
 */

namespace Tests\Unit\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Hanaboso\PipesFramework\TopologyGenerator\Request\RequestHandler;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Tests\KernelTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class StartingPointHandlerTest
 *
 * @package Tests\Unit\HbPFConfiguratorBundle\Handler
 */
class StartingPointHandlerTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @covers StartingPointHandler::getTopology()
     */
    public function testGetTopologyException(): void
    {
        $dr = $this->createMock(DocumentRepository::class);
        $dr->expects($this->at(0))->method('find')->willReturn(NULL);

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        /** @var EventDispatcher|PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->willReturn('');

        /** @var RequestHandler|PHPUnit_Framework_MockObject_MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint, $dispatcher, $requestHandler);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The topology[id=123] does not exist.');
        $startingPointHandler->runWithRequestById(Request::createFromGlobals(), '123', '1');
    }

    /**
     * @covers StartingPointHandler::getNode()
     */
    public function testGetNodeException(): void
    {
        $dr = $this->createMock(DocumentRepository::class);
        $dr->expects($this->at(0))->method('find')->willReturn(new Topology());
        $dr->expects($this->at(1))->method('find')->willReturn(NULL);

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        /** @var EventDispatcher|PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->willReturn('');

        /** @var RequestHandler|PHPUnit_Framework_MockObject_MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint, $dispatcher, $requestHandler);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The node[id=1] does not exist.');
        $startingPointHandler->runWithRequestById(Request::createFromGlobals(), '123', '1');
    }

    /**
     * @covers StartingPointHandler::getTopology()
     */
    public function testGetTopologiesException(): void
    {
        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        /** @var EventDispatcher|PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->willReturn('');

        /** @var RequestHandler|PHPUnit_Framework_MockObject_MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);

        $startingPointHandler = new StartingPointHandler($this->dm, $startingPoint, $dispatcher, $requestHandler);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The topology[name=123] does not exist.');
        $startingPointHandler->runWithRequest(Request::createFromGlobals(), '123', '1');
    }

    /**
     * @covers StartingPointHandler::getNode()
     */
    public function testGetNodeByNameException(): void
    {
        $top = new Topology();
        $top
            ->setName('123')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);

        $this->setProperty($top, 'id', '1');

        /** @var StartingPoint|PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        /** @var EventDispatcher|PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->willReturn('');

        /** @var RequestHandler|PHPUnit_Framework_MockObject_MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);

        $nodeRepo = $this->createMock(NodeRepository::class);
        $nodeRepo->method('getNodeByTopology')->willReturn(NULL);
        $nodeRepo->method('find')->willReturn(new Node());

        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->method('getRunnableTopologies')->willReturn([$top]);
        $topRepo->method('find')->willReturn($top);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))->method('getRepository')->willReturn($nodeRepo);
        $dm->expects($this->at(1))->method('getRepository')->willReturn($topRepo);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint, $dispatcher, $requestHandler);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The node[name=1] does not exist.');
        $startingPointHandler->runWithRequest(Request::createFromGlobals(), '123', '1');
    }

    /**
     * @covers StartingPointHandler::getTopology()
     * @covers StartingPointHandler::getNode()
     * @covers StartingPointHandler::runWithRequest()
     */
    public function testRunWithRequest(): void
    {
        $tops  = [];
        $nodes = [];
        for ($i = 0; $i < 2; $i++) {
            $top = new Topology();
            $top
                ->setName('123')
                ->setEnabled(TRUE)
                ->setVisibility(TopologyStatusEnum::PUBLIC);

            $this->setProperty($top, 'id', (string) $i);

            $node = new Node();
            $node
                ->setName('1')
                ->setEnabled(TRUE)
                ->setTopology($top->getId());

            $tops[]  = $top;
            $nodes[] = $node;
        }

        $nodeRepo = $this->createMock(NodeRepository::class);
        $nodeRepo->method('getNodeByTopology')->willReturn($nodes[0]);
        $nodeRepo->method('find')->willReturn($nodes[0]);

        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->method('getRunnableTopologies')->willReturn($tops);
        $topRepo->method('find')->willReturn($tops[0]);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))->method('getRepository')->willReturn($nodeRepo);
        $dm->expects($this->at(1))->method('getRepository')->willReturn($topRepo);

        /** @var EventDispatcher|PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->willReturn('');

        /** @var RequestHandler|PHPUnit_Framework_MockObject_MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint        = $this->createMock(StartingPoint::class);
        $startingPointHandler = new StartingPointHandler($dm, $startingPoint, $dispatcher, $requestHandler);
        $startingPointHandler->runWithRequest(Request::createFromGlobals(), '123', '1');
    }

    /**
     * @covers StartingPointHandler::getTopology()
     * @covers StartingPointHandler::getNode()
     * @covers StartingPointHandler::run()
     */
    public function testRun(): void
    {
        $top = new Topology();
        $top
            ->setName('123')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->setProperty($top, 'id', '1');

        $node = new Node();
        $node
            ->setName('1')
            ->setEnabled(TRUE)
            ->setTopology($top->getId());

        $nodeRepo = $this->createMock(NodeRepository::class);
        $nodeRepo->method('getNodeByTopology')->willReturn($node);
        $nodeRepo->method('find')->willReturn($node);

        $topRepo = $this->createMock(TopologyRepository::class);
        $topRepo->method('getRunnableTopologies')->willReturn([$top]);
        $topRepo->method('find')->willReturn($top);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))->method('getRepository')->willReturn($nodeRepo);
        $dm->expects($this->at(1))->method('getRepository')->willReturn($topRepo);

        /** @var EventDispatcher|PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->willReturn('');

        /** @var RequestHandler|PHPUnit_Framework_MockObject_MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);

        /** @var StartingPoint|PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint        = $this->createMock(StartingPoint::class);
        $startingPointHandler = new StartingPointHandler($dm, $startingPoint, $dispatcher, $requestHandler);
        $startingPointHandler->run('123', '1');
    }

    /**
     * @covers StartingPointHandler::runTest()
     */
    public function testRunTest(): void
    {
        $top = new Topology();
        $this->setProperty($top, 'id', '123');
        $dr = $this->createMock(TopologyRepository::class);
        $dr->expects($this->at(0))->method('find')->willReturn($top);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        $data = [
            'status'  => TRUE,
            'message' => '5/5 Nodes OK',
            'failed'  => [],
        ];

        /** @var EventDispatcher|PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->willReturn('');

        /** @var RequestHandler|PHPUnit_Framework_MockObject_MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);

        /** @var StartingPoint|PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);
        $startingPoint->method('runTest')->willReturn($data);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint, $dispatcher, $requestHandler);

        $result = $startingPointHandler->runTest('123');

        $this->assertEquals($data, $result);
    }

    /**
     * @covers StartingPointHandler::runTest()
     */
    public function testRunTestException(): void
    {
        $dr = $this->createMock(TopologyRepository::class);
        $dr->expects($this->at(0))->method('findOneBy')->willReturn(NULL);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        /** @var StartingPoint|PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        /** @var EventDispatcher|PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->willReturn('');

        /** @var RequestHandler|PHPUnit_Framework_MockObject_MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint, $dispatcher, $requestHandler);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The topology[id=123] does not exist.');
        $startingPointHandler->runTest('123');
    }

}