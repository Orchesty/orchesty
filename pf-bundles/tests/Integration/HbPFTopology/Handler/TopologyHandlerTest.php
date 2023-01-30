<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\HbPFTopology\Handler;

use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;
use Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Configurator\Model\TopologyTester;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler\UserTaskHandler;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository;
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
        $handler = self::getContainer()->get('hbpf.configurator.handler.topology');
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

        $manager     = $this->mockManager($topology);
        $nodeManager = $this->mockNodeManager();
        $generator   = self::createPartialMock(TopologyGeneratorBridge::class, ['generateTopology', 'runTopology']);
        $generator->expects(self::any())->method('generateTopology')->willReturn(new ResponseDto(200, '', '{}', []));
        $generator->expects(self::any())->method('runTopology')->willReturn(new ResponseDto(200, '', '{}', []));
        $userTaskHandler = $this->mockUserTaskHandler();
        $topologyTester  = $this->mockTopologyTester();

        $dml     = self::getContainer()->get('hbpf.database_manager_locator');
        $handler = new TopologyHandler($dml, $manager, $nodeManager, $generator, $userTaskHandler, $topologyTester);
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
        $topology        = $this->createTopology();
        $manager         = $this->mockManager($topology);
        $nodeManager     = $this->mockNodeManager();
        $generator       = $this->mockGenerator(new ResponseDto(400, '', '{}', []));
        $userTaskHandler = $this->mockUserTaskHandler();
        $topologyTester  = $this->mockTopologyTester();

        $dml     = self::getContainer()->get('hbpf.database_manager_locator');
        $handler = new TopologyHandler($dml, $manager, $nodeManager, $generator, $userTaskHandler, $topologyTester);
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
        $topology        = $this->createTopology();
        $manager         = $this->mockManager($topology);
        $nodeManager     = $this->mockNodeManager();
        $generator       = $this->mockGenerator(new MappingException());
        $userTaskHandler = $this->mockUserTaskHandler();
        $topologyTester  = $this->mockTopologyTester();

        $dml     = self::getContainer()->get('hbpf.database_manager_locator');
        $handler = new TopologyHandler($dml, $manager, $nodeManager, $generator, $userTaskHandler, $topologyTester);
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

        $handler = self::getContainer()->get('hbpf.configurator.handler.topology');
        $result  = $handler->cloneTopology($topology->getId());

        self::assertEquals(10, count($result));
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::deleteTopology
     *
     * @throws Exception
     */
    public function testDeleteTopology(): void
    {
        $topology = $this->createTopology()->setEnabled(FALSE);

        $manager         = $this->mockManager($topology);
        $nodeManager     = $this->mockNodeManager();
        $generator       = $this->mockGenerator(new ResponseDto(200, '', '{}', []));
        $userTaskHandler = $this->mockUserTaskHandler();
        $topologyTester  = $this->mockTopologyTester();

        $dml     = self::getContainer()->get('hbpf.database_manager_locator');
        $handler = new TopologyHandler($dml, $manager, $nodeManager, $generator, $userTaskHandler, $topologyTester);
        $result  = $handler->deleteTopology($topology->getId());

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
        self::getContainer()->get('hbpf.configurator.manager.sdk')->create([Sdk::URL => '127.0.0.2', Sdk::NAME => '']);

        $manager         = $this->mockManager($topology);
        $nodeManager     = $this->mockNodeManager();
        $generator       = $this->mockGenerator(new ResponseDto(200, '', '{"docker_info": {"info": 1}}', []));
        $userTaskHandler = $this->mockUserTaskHandler();
        $topologyTester  = self::getContainer()->get('hbpf.topology.tester');
        $this->setProperty($topologyTester, 'topologyConfigFactory', $this->mockTopologyNodeFactory());
        $this->setProperty($topologyTester, 'nodeRepository', $this->mockNodeRepository());

        $dml     = self::getContainer()->get('hbpf.database_manager_locator');
        $handler = new TopologyHandler($dml, $manager, $nodeManager, $generator, $userTaskHandler, $topologyTester);
        $result  = $handler->runTest($topology->getId());

        self::assertEquals([
            [
                'id'     => 'node-id',
                'name'   => 'node-name',
                'status' => 'ok',
            ],
            [
                'id'     => 'node-id-exception',
                'name'   => 'node-name-exception',
                'status' => 'nok',
                'reason' => 'cURL error 6: Could not resolve host: unknown (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for http://unknown',
            ],
        ], $result);
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
    private function mockGenerator(mixed $return): TopologyGeneratorBridge
    {
        $generator = self::createPartialMock(
            TopologyGeneratorBridge::class,
            [
                'generateTopology',
                'runTopology',
                'deleteTopology',
                'stopTopology',
                'invalidateTopologyCache',
            ],
        );

        if ($return instanceof Throwable) {
            $generator->expects(self::any())->method('generateTopology')->willThrowException($return);
            $generator->expects(self::any())->method('runTopology')->willThrowException($return);
            $generator->expects(self::any())->method('deleteTopology')->willThrowException($return);
            $generator->expects(self::any())->method('stopTopology')->willThrowException($return);

        } else {
            $generator->expects(self::any())->method('generateTopology')->willReturn($return);
            $generator->expects(self::any())->method('runTopology')->willReturn($return);
            $generator->expects(self::any())->method('deleteTopology')->willReturn($return);
            $generator->expects(self::any())->method('stopTopology')->willReturn($return);
        }
        $generator->expects(self::any())->method('invalidateTopologyCache')->willReturn([]);

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

    /**
     * @return NodeManager
     */
    private function mockNodeManager(): NodeManager
    {
        return self::createPartialMock(
            NodeManager::class,
            [],
        );
    }

    /**
     * @return UserTaskHandler
     */
    private function mockUserTaskHandler(): UserTaskHandler
    {
        return self::createPartialMock(
            UserTaskHandler::class,
            [],
        );
    }

    /**
     * @return TopologyTester
     */
    private function mockTopologyTester(): TopologyTester
    {
        return self::createPartialMock(
            TopologyTester::class,
            [],
        );
    }

    /**
     * @return TopologyConfigFactory
     */
    private function mockTopologyNodeFactory(): TopologyConfigFactory
    {
        $topologyConfigFactory = self::createMock(TopologyConfigFactory::class);
        $topologyConfigFactory->method('getWorkers')->willReturn([
            'settings' => [
                'host'        => 'example.com',
                'port'        => 80,
                'status_path' => '',
            ],
        ], [
            'settings' => [
                'host'        => 'unknown',
                'port'        => 80,
                'status_path' => '',
            ],
        ]);

        return $topologyConfigFactory;
    }

    /**
     * @return NodeRepository
     */
    private function mockNodeRepository(): NodeRepository
    {
        $nodeOne = (new Node())->setName('node-name');
        $nodeTwo = (new Node())->setName('node-name-exception');
        $this->setProperty($nodeOne, 'id', 'node-id');
        $this->setProperty($nodeTwo, 'id', 'node-id-exception');

        $nodeRepository = self::createMock(NodeRepository::class);
        $nodeRepository->method('getNodesByTopology')->willReturn([$nodeOne, $nodeTwo]);

        return $nodeRepository;
    }

}
