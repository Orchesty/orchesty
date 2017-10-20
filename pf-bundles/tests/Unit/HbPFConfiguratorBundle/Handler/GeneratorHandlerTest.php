<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.9.17
 * Time: 11:27
 */

namespace Tests\Unit\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\DestroyTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\GenerateTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\StartTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\StopTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\TopologyActionsFactory;
use Hanaboso\PipesFramework\TopologyGenerator\Exception\TopologyGeneratorException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class GeneratorHandlerTest
 *
 * @package Tests\Unit\HbPFConfiguratorBundle\Handler
 */
class GeneratorHandlerTest extends TestCase
{

    /**
     * @covers       GeneratorHandler::generateTopology()
     * @dataProvider generateTopology
     *
     * @param DocumentManager $dm
     * @param bool            $topologyActionReturn
     * @param null|string     $exception
     */
    public function testGenerateTopology(DocumentManager $dm, bool $topologyActionReturn, ?string $exception): void
    {
        $action = $this
            ->getMockBuilder(GenerateTopologyActions::class)
            ->setMethods(['generateTopology'])
            ->disableOriginalConstructor()
            ->getMock();
        $action
            ->expects($exception ? $this->never() : $this->once())
            ->method('generateTopology')
            ->willReturn($topologyActionReturn);

        $topologyActionFactory = $this
            ->getMockBuilder(TopologyActionsFactory::class)
            ->setMethods(['getTopologyAction'])
            ->disableOriginalConstructor()
            ->getMock();

        $topologyActionFactory
            ->expects($exception ? $this->never() : $this->once())
            ->method('getTopologyAction')
            ->with(TopologyActionsFactory::GENERATE)
            ->willReturn($action);

        if ($exception) {
            $this->expectException($exception);
        }

        /** @var TopologyActionsFactory $topologyActionFactory */
        $handler = new GeneratorHandler($dm, '/srv/directory', 'demo_network', $topologyActionFactory);
        $this->assertEquals($topologyActionReturn, $handler->generateTopology("ABCD123456"));
    }

    /**
     * @covers       GeneratorHandler::runTopology()
     * @dataProvider runTopology
     *
     * @param DocumentManager $dm
     * @param bool            $topologyActionReturn
     * @param array|null      $getTopologyInfo
     * @param null|string     $exception
     */
    public function testRunTopology(DocumentManager $dm, bool $topologyActionReturn, ?array $getTopologyInfo,
                                    ?string $exception): void
    {
        $action = $this
            ->getMockBuilder(StartTopologyActions::class)
            ->setMethods(['runTopology', 'getTopologyInfo'])
            ->disableOriginalConstructor()
            ->getMock();
        $action
            ->expects($exception ? $this->never() : $this->once())
            ->method('runTopology')
            ->willReturn($topologyActionReturn);

        $action
            ->expects(($exception || $getTopologyInfo == NULL) ? $this->never() : $this->once())
            ->method('getTopologyInfo')
            ->willReturn($getTopologyInfo);

        $topologyActionFactory = $this
            ->getMockBuilder(TopologyActionsFactory::class)
            ->setMethods(['getTopologyAction'])
            ->disableOriginalConstructor()
            ->getMock();

        $topologyActionFactory
            ->expects($exception ? $this->never() : $this->once())
            ->method('getTopologyAction')
            ->with(TopologyActionsFactory::START)
            ->willReturn($action);

        if ($exception) {
            $this->expectException($exception);
        }

        /** @var TopologyActionsFactory $topologyActionFactory */
        $handler = new GeneratorHandler($dm, '/srv/directory', 'demo_network', $topologyActionFactory);
        $this->assertEquals($getTopologyInfo, $handler->runTopology("ABCD123456"));
    }

    /**
     * @dataProvider stopTopology
     * @covers       GeneratorHandler::stopTopology()
     *
     * @param DocumentManager $dm
     * @param bool            $topologyActionReturn
     * @param array|null      $getTopologyInfo
     * @param null|string     $exception
     */
    public function testStopTopology(DocumentManager $dm, bool $topologyActionReturn, ?array $getTopologyInfo,
                                     ?string $exception): void
    {
        $action = $this
            ->getMockBuilder(StopTopologyActions::class)
            ->setMethods(['stopTopology', 'getTopologyInfo'])
            ->disableOriginalConstructor()
            ->getMock();
        $action
            ->expects($exception ? $this->never() : $this->once())
            ->method('stopTopology')
            ->willReturn($topologyActionReturn);

        $action
            ->expects(($exception || $getTopologyInfo == NULL) ? $this->never() : $this->once())
            ->method('getTopologyInfo')
            ->willReturn($getTopologyInfo);

        $topologyActionFactory = $this
            ->getMockBuilder(TopologyActionsFactory::class)
            ->setMethods(['getTopologyAction'])
            ->disableOriginalConstructor()
            ->getMock();

        $topologyActionFactory
            ->expects($exception ? $this->never() : $this->once())
            ->method('getTopologyAction')
            ->with(TopologyActionsFactory::STOP)
            ->willReturn($action);

        if ($exception) {
            $this->expectException($exception);
        }

        /** @var TopologyActionsFactory $topologyActionFactory */
        $handler = new GeneratorHandler($dm, '/srv/directory', 'demo_network', $topologyActionFactory);
        $this->assertEquals($getTopologyInfo, $handler->stopTopology("ABCD123456"));
    }

    /**
     * @dataProvider destroyTopology
     * @covers       GeneratorHandler::destroyTopology()
     *
     * @param DocumentManager $dm
     * @param bool            $topologyActionReturn
     * @param bool|null       $getTopologyInfo
     * @param null|string     $exception
     */
    public function testDestroyTopology(DocumentManager $dm, bool $topologyActionReturn, ?bool $getTopologyInfo,
                                        ?string $exception): void
    {
        $action = $this
            ->getMockBuilder(DestroyTopologyActions::class)
            ->setMethods(['deleteTopologyDir', 'deleteQueues'])
            ->disableOriginalConstructor()
            ->getMock();
        $action
            ->expects($exception ? $this->never() : $this->once())
            ->method('deleteTopologyDir')
            ->willReturn($topologyActionReturn);

        $action
            ->expects(($exception) ? $this->never() : $this->once())
            ->method('deleteQueues')
            ->willReturn($getTopologyInfo);

        $topologyActionFactory = $this
            ->getMockBuilder(TopologyActionsFactory::class)
            ->setMethods(['getTopologyAction'])
            ->disableOriginalConstructor()
            ->getMock();

        $topologyActionFactory
            ->expects($exception ? $this->never() : $this->once())
            ->method('getTopologyAction')
            ->with(TopologyActionsFactory::DESTROY)
            ->willReturn($action);

        if ($exception) {
            $this->expectException($exception);
        }

        /** @var TopologyActionsFactory $topologyActionFactory */
        $handler = new GeneratorHandler($dm, '/srv/directory', 'demo_network', $topologyActionFactory);
        $this->assertEquals($getTopologyInfo, $handler->destroyTopology("ABCD123456"));
    }

    /**
     * @dataProvider infoTopology
     * @covers       GeneratorHandler::infoTopology()
     *
     * @param DocumentManager $dm
     * @param array|null      $getTopologyInfo
     * @param null|string     $exception
     */
    public function testInfoTopology(DocumentManager $dm, ?array $getTopologyInfo, ?string $exception): void
    {
        $action = $this
            ->getMockBuilder(StopTopologyActions::class)
            ->setMethods(['getTopologyInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $action
            ->expects(($exception || $getTopologyInfo == NULL) ? $this->never() : $this->once())
            ->method('getTopologyInfo')
            ->willReturn($getTopologyInfo);

        $topologyActionFactory = $this
            ->getMockBuilder(TopologyActionsFactory::class)
            ->setMethods(['getTopologyAction'])
            ->disableOriginalConstructor()
            ->getMock();

        $topologyActionFactory
            ->expects($exception ? $this->never() : $this->once())
            ->method('getTopologyAction')
            ->with(TopologyActionsFactory::START)
            ->willReturn($action);

        if ($exception) {
            $this->expectException($exception);
        }

        /** @var TopologyActionsFactory $topologyActionFactory */
        $handler = new GeneratorHandler($dm, '/srv/directory', 'demo_network', $topologyActionFactory);
        $this->assertEquals($getTopologyInfo, $handler->infoTopology("ABCD123456"));
    }

    /**
     * @return array
     */
    public function infoTopology(): array
    {
        return [
            [$this->createDm(), [1], NULL],
            [$this->createDm(), [1], NULL],
            [$this->createDm(FALSE), [1], TopologyGeneratorException::class],
        ];
    }

    /**
     * @return array
     */
    public function destroyTopology(): array
    {
        return [
            [$this->createDm(), TRUE, TRUE, NULL],
            [$this->createDm(), FALSE, NULL, NULL],
            [$this->createDm(FALSE), FALSE, NULL, TopologyGeneratorException::class],
        ];
    }

    /**
     * @return array
     */
    public function stopTopology(): array
    {
        return [
            [$this->createDm(), TRUE, [1], NULL],
            [$this->createDm(), FALSE, NULL, NULL],
            [$this->createDm(FALSE), FALSE, NULL, TopologyGeneratorException::class],
        ];
    }

    /**
     * @return array
     */
    public function runTopology(): array
    {
        return [
            [$this->createDm(), TRUE, [1], NULL],
            [$this->createDm(), FALSE, NULL, NULL],
            [$this->createDm(FALSE), FALSE, NULL, TopologyGeneratorException::class],
        ];
    }

    /**
     * @return array
     */
    public function generateTopology(): array
    {

        return [
            [$this->createDm(), TRUE, NULL],
            [$this->createDm(), FALSE, NULL],
            [$this->createDm(FALSE), FALSE, TopologyGeneratorException::class],
            [$this->createDm(TRUE, FALSE), FALSE, TopologyGeneratorException::class],
        ];
    }

    /**
     * @param bool $topology
     * @param bool $nodes
     *
     * @return DocumentManager
     */
    protected function createDm(bool $topology = TRUE, bool $nodes = TRUE): DocumentManager
    {
        /** @var DocumentRepository|PHPUnit_Framework_MockObject_MockObject $repositoryTopology */
        $repositoryTopology = $this->createMock(DocumentRepository::class);
        $repositoryTopology->method('find')->willReturn($topology ? new Topology() : NULL);

        /** @var DocumentRepository|PHPUnit_Framework_MockObject_MockObject $repositoryNode */
        $repositoryNode = $this->createMock(DocumentRepository::class);
        $repositoryNode->method('findBy')->willReturn($nodes ? [new Node()] : []);

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
        /**
         * @param $class
         *
         * @return DocumentRepository|null
         */
            function ($class) use ($repositoryTopology, $repositoryNode): ?DocumentRepository {
                if ($class == Topology::class) {

                    return $repositoryTopology;
                } elseif ($class == Node::class) {

                    return $repositoryNode;
                }

                return NULL;
            });

        return $dm;
    }

}
