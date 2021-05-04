<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\HbPFTopology\Handler;

use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Throwable;

/**
 * Class TopologyHandlerTest
 *
 * @package PipesFrameworkTests\Integration\HbPFTopology\Handler
 */
final class TopologyHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getCronTopologies
     *
     * @throws Exception
     */
    public function testGetCronTopologies(): void
    {
        $handler = self::$container->get('hbpf.configurator.handler.topology');
        $result  = $handler->getCronTopologies();

        self::assertArrayHasKey('items', $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::publishTopology
     *
     * @throws Exception
     */
    public function testPublishTopology(): void
    {
        $topology = $this->createTopology();

        $manager   = $this->mockManager($topology);
        $generator = self::createPartialMock(TopologyGeneratorBridge::class, ['generateTopology', 'runTopology']);
        $generator->expects(self::any())->method('generateTopology')->willReturn(new ResponseDto(200, '', '{}', []));
        $generator->expects(self::any())->method('runTopology')->willReturn(new ResponseDto(200, '', '{}', []));

        $dml     = self::$container->get('hbpf.database_manager_locator');
        $handler = new TopologyHandler($dml, $manager, $generator);
        $result  = $handler->publishTopology($topology->getId());

        self::assertEquals(200, $result->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::publishTopology
     *
     * @throws Exception
     */
    public function testPublishTopologyErr(): void
    {
        $topology  = $this->createTopology();
        $manager   = $this->mockManager($topology);
        $generator = $this->mockGenerator(new ResponseDto(400, '', '{}', []));

        $dml     = self::$container->get('hbpf.database_manager_locator');
        $handler = new TopologyHandler($dml, $manager, $generator);
        $result  = $handler->publishTopology($topology->getId());

        self::assertEquals(400, $result->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::publishTopology
     *
     * @throws Exception
     */
    public function testPublishTopologyErr2(): void
    {
        $topology  = $this->createTopology();
        $manager   = $this->mockManager($topology);
        $generator = $this->mockGenerator(new MappingException());

        $dml     = self::$container->get('hbpf.database_manager_locator');
        $handler = new TopologyHandler($dml, $manager, $generator);
        $result  = $handler->publishTopology($topology->getId());

        self::assertEquals(400, $result->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::cloneTopology
     *
     * @throws Exception
     */
    public function testCloneTopology(): void
    {
        $topology = $this->createTopology();

        $handler = self::$container->get('hbpf.configurator.handler.topology');
        $result  = $handler->cloneTopology($topology->getId());

        self::assertEquals(9, count($result));
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::deleteTopology
     *
     * @throws Exception
     */
    public function testDeleteTopology(): void
    {
        $topology = $this->createTopology()->setEnabled(FALSE);

        $manager   = $this->mockManager($topology);
        $generator = $this->mockGenerator(new ResponseDto(200, '', '{}', []));
        $dml       = self::$container->get('hbpf.database_manager_locator');
        $handler   = new TopologyHandler($dml, $manager, $generator);

        $result = $handler->deleteTopology($topology->getId());

        self::assertEquals(200, $result->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::runTest
     *
     * @throws Exception
     */
    public function testRunTest(): void
    {
        $topology = $this->createTopology();

        $manager   = $this->mockManager($topology);
        $generator = $this->mockGenerator(new ResponseDto(200, '', '{"docker_info": {"info": 1}}', []));
        $dml       = self::$container->get('hbpf.database_manager_locator');
        $handler   = new TopologyHandler($dml, $manager, $generator);

        $result = $handler->runTest($topology->getId());

        self::assertEquals([], $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::runTest
     *
     * @throws Exception
     */
    public function testRunTestStartTopo(): void
    {
        $topology = $this->createTopology();

        $manager   = $this->mockManager($topology);
        $generator = $this->mockGenerator(new ResponseDto(200, '', '', []));
        $dml       = self::$container->get('hbpf.database_manager_locator');
        $handler   = new TopologyHandler($dml, $manager, $generator);

        $result = $handler->runTest($topology->getId());

        self::assertEquals([], $result);
    }

    /**
     * @return Topology
     * @throws Exception
     */
    private function createTopology(): Topology
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->dm->persist($topology);
        $node = (new Node())->setTopology($topology->getId());
        $this->pfd($node);

        return $topology;
    }

    /**
     * @param mixed $return
     *
     * @return TopologyGeneratorBridge
     */
    private function mockGenerator($return): TopologyGeneratorBridge
    {
        $generator = self::createPartialMock(
            TopologyGeneratorBridge::class,
            [
                'generateTopology',
                'runTopology',
                'deleteTopology',
                'stopTopology',
                'invalidateTopologyCache',
                'infoTopology',
                'runTest',
            ],
        );

        if ($return instanceof Throwable) {
            $generator->expects(self::any())->method('generateTopology')->willThrowException($return);
            $generator->expects(self::any())->method('runTopology')->willThrowException($return);
            $generator->expects(self::any())->method('deleteTopology')->willThrowException($return);
            $generator->expects(self::any())->method('stopTopology')->willThrowException($return);
            $generator->expects(self::any())->method('infoTopology')->willThrowException($return);

        } else {
            $generator->expects(self::any())->method('generateTopology')->willReturn($return);
            $generator->expects(self::any())->method('runTopology')->willReturn($return);
            $generator->expects(self::any())->method('deleteTopology')->willReturn($return);
            $generator->expects(self::any())->method('stopTopology')->willReturn($return);
            $generator->expects(self::any())->method('infoTopology')->willReturn($return);
        }
        $generator->expects(self::any())->method('invalidateTopologyCache')->willReturn([]);
        $generator->expects(self::any())->method('runTest')->willReturn([]);

        return $generator;
    }

    /**
     * @param Topology $topology
     *
     * @return TopologyManager
     */
    private function mockManager(Topology $topology): TopologyManager
    {
        $manager = self::createPartialMock(
            TopologyManager::class,
            ['publishTopology', 'unPublishTopology', 'deleteTopology'],
        );
        $manager->expects(self::any())->method('publishTopology')->willReturn($topology);
        $manager->expects(self::any())->method('unPublishTopology')->willReturn($topology);
        $manager->expects(self::any())->method('deleteTopology');

        return $manager;
    }

}
