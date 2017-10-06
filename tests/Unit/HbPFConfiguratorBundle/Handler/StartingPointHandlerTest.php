<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 3:46 PM
 */

namespace Tests\Unit\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Symfony\Component\HttpFoundation\Request;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class StartingPointHandlerTest
 *
 * @package Tests\Unit\HbPFConfiguratorBundle\Handler
 */
class StartingPointHandlerTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @covers StartingPointHandler::getTopology()
     */
    public function testGetTopologyException(): void
    {


        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($this->dm, $startingPoint);

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
        $top
            ->setName('123')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);

        $this->persistAndFlush($top);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint = $this->createMock(StartingPoint::class);

        $startingPointHandler = new StartingPointHandler($this->dm, $startingPoint);

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
        for ($i = 0; $i < 2; $i++) {
            $top = new Topology();
            $top
                ->setName('123')
                ->setEnabled(TRUE)
                ->setVisibility(TopologyStatusEnum::PUBLIC);

            $this->persistAndFlush($top);

            $node = new Node();
            $node
                ->setName('1')
                ->setEnabled(TRUE)
                ->setTopology($top->getId());

            $this->persistAndFlush($node);
        }

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint        = $this->createMock(StartingPoint::class);
        $startingPointHandler = new StartingPointHandler($this->dm, $startingPoint);
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

        $this->persistAndFlush($top);

        $node = new Node();
        $node
            ->setName('1')
            ->setEnabled(TRUE)
            ->setTopology($top->getId());

        $this->persistAndFlush($node);

        /** @var StartingPoint|\PHPUnit_Framework_MockObject_MockObject $startingPoint */
        $startingPoint        = $this->createMock(StartingPoint::class);
        $startingPointHandler = new StartingPointHandler($this->dm, $startingPoint);
        $startingPointHandler->run('123', '1');
    }

    /**
     * @covers StartingPointHandler::getTopology()
     * @covers StartingPointHandler::runTest()
     */
    public function testRunTest(): void
    {
        $top = new Topology();
        $this->setProperty($top, 'id', '123');
        $dr = $this->createMock(TopologyRepository::class);
        $dr->expects($this->at(0))->method('findOneBy')->willReturn($top);

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