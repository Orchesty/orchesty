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
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Tests\PrivateTrait;

/**
 * Class StartingPointHandlerTest
 *
 * @package Tests\Unit\HbPFConfiguratorBundle\Handler
 */
class StartingPointHandlerTest extends TestCase
{

    use PrivateTrait;

    /**
     * @covers StartingPointHandler::getTopology()
     */
    public function testGetTopologyException(): void
    {
        $dr = $this->createMock(DocumentRepository::class);
        $dr->expects($this->at(0))->method('findBy')->willReturn(NULL);

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The topology[name=123] does not exist.');
        $startingPointHandler->runWithRequest(Request::createFromGlobals(), '123', '1');
    }

    /**
     * @covers StartingPointHandler::getNode()
     */
    public function testGetNodeException(): void
    {
        $top = new Topology();
        $this->setProperty($top, 'id', '');
        $dr = $this->createMock(DocumentRepository::class);
        $dr->expects($this->at(0))->method('findBy')->willReturn([$top]);
        $dr->expects($this->at(1))->method('findOneBy')->willReturn(NULL);

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint);

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
        $top = new Topology();
        $this->setProperty($top, 'id', '');
        $dr = $this->createMock(DocumentRepository::class);
        $dr->expects($this->at(0))->method('findBy')->willReturn([$top]);
        $dr->expects($this->at(1))->method('findOneBy')->willReturn((new Node()));

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint);

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
        $this->setProperty($top, 'id', '');
        $dr = $this->createMock(DocumentRepository::class);
        $dr->expects($this->at(0))->method('findBy')->willReturn([$top]);
        $dr->expects($this->at(1))->method('findOneBy')->willReturn(new Node());

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint);

        $startingPointHandler->run('123', '1');
    }

    /**
     * @covers StartingPointHandler::getTopology()
     * @covers StartingPointHandler::runTest()
     */
    public function testRunTest(): void
    {
        $top = new Topology();
        $this->setProperty($top, 'id', '');
        $dr = $this->createMock(DocumentRepository::class);
        $dr->expects($this->at(0))->method('findBy')->willReturn([$top]);

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        $data = [
            'status'  => TRUE,
            'message' => '5/5 Nodes OK',
            'failed'  => [],
        ];

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);
        $startingPoint->method('runTest')->willReturn($data);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint);

        $result = $startingPointHandler->runTest('123');

        $this->assertEquals($data, $result[0]);
    }

}