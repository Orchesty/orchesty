<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 3:46 PM
 */

namespace Tests\Unit\ApiGateway\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Commons\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler\StartingPointHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StartingPointHandlerTest
 *
 * @package Tests\Unit\ApiGateway\Handler
 */
class StartingPointHandlerTest extends TestCase
{

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

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The topology[id=123] does not exist.');
        $startingPointHandler->runWithRequest(Request::createFromGlobals(), '123', '1');
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

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The node[id=1] does not exist.');
        $startingPointHandler->runWithRequest(Request::createFromGlobals(), '123', '1');
    }

    /**
     * @covers StartingPointHandler::getTopology()
     * @covers StartingPointHandler::getNode()
     * @covers StartingPointHandler::runWithRequest()
     */
    public function testRunWithRequest(): void
    {
        $dr = $this->createMock(DocumentRepository::class);
        $dr->expects($this->at(0))->method('find')->willReturn(new Topology());
        $dr->expects($this->at(1))->method('find')->willReturn(new Node());

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
        $dr = $this->createMock(DocumentRepository::class);
        $dr->expects($this->at(0))->method('find')->willReturn(new Topology());
        $dr->expects($this->at(1))->method('find')->willReturn(new Node());

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($dr);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($dm, $startingPoint);

        $startingPointHandler->run('123', '1');
    }

}