<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Model;

use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory;
use Hanaboso\PipesPhpSdk\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository;
use Hanaboso\Utils\String\Json;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyConfigFactoryTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Model
 */
class TopologyConfigFactoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::create
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getEnvParameters
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::loopNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::assembleNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getNextNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getWorkers
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getWorkerByType
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getPublishQueue
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getFaucet
     *
     * @throws Exception
     */
    public function testCreate(): void
    {
        $settings = new SystemConfigDto('someSdkHost', '', 10);

        $node1 = (new Node())->setTopology('123')->setType(TypeEnum::WEBHOOK)->setName('example1');
        $node2 = (new Node())->setTopology('123')->setName('example2')->setSystemConfigs($settings)
            ->setType(TypeEnum::CONNECTOR);
        $node3 = (new Node())->setTopology('123')->setName('example3')->setType(TypeEnum::BATCH_CONNECTOR);
        $node4 = (new Node())->setTopology('123')->setName('example4')->setType(TypeEnum::CONNECTOR);
        $node5 = (new Node())->setTopology('123')->setName('example5')->setType(TypeEnum::USER);

        $this->pfd($node1);
        $this->pfd($node2);

        $embedNode = new EmbedNode();
        $embedNode->setName('embedNode2');
        $this->setProperty($embedNode, 'id', $node2->getId());
        $node1->addNext($embedNode);
        $this->pfd($node1);

        $this->pfd($embedNode);
        $this->pfd($node3);
        $this->pfd($node4);
        $this->pfd($node5);

        /** @var NodeRepository $nodeRepository */
        $nodeRepository = $this->dm->getRepository(Node::class);
        $nodes          = $nodeRepository->getNodesByTopology('123');

        $configFactory = self::$container->get('hbpf.topology.configurator');
        $result        = $configFactory->create($nodes);
        $arr           = Json::decode($result);

        self::assertArrayNotHasKey(TopologyConfigFactory::WORKER, $arr);
        self::assertArrayNotHasKey(TopologyConfigFactory::SETTINGS, $arr);

        self::assertResult(__DIR__ . '/data/topologyConfigFactory.json', $arr);
        self::assertEquals(5, count($arr[TopologyConfigFactory::NODE_CONFIG]));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getWorkerByType
     *
     * @throws Exception
     */
    public function testGetWorkers(): void
    {
        $configFactory = self::$container->get('hbpf.topology.configurator');
        $node          = (new Node())->setTopology('123')->setType(TypeEnum::RESEQUENCER)->setName('example1');

        $result = $this->invokeMethod($configFactory, 'getWorkerByType', [$node]);
        self::assertEquals('worker.resequencer', $result);

        $node->setType(TypeEnum::SPLITTER);
        $result = $this->invokeMethod($configFactory, 'getWorkerByType', [$node]);
        self::assertEquals('splitter.json', $result);

        $node->setType(TypeEnum::XML_PARSER);
        $result = $this->invokeMethod($configFactory, 'getWorkerByType', [$node]);
        self::assertEquals('worker.http_xml_parser', $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getPaths
     *
     * @throws Exception
     */
    public function testGetPaths(): void
    {
        $configFactory = self::$container->get('hbpf.topology.configurator');
        $node          = (new Node())->setTopology('123')->setType(TypeEnum::XML_PARSER)->setName('example1');

        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
        self::assertEquals(
            [
                'process_path' => '/xml_parser',
                'status_path'  => '/xml_parser/test',
            ],
            $result
        );

        $node->setType(TypeEnum::TABLE_PARSER);
        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
        self::assertEquals(
            [
                'process_path' => '/parser/json/to/example1/',
                'status_path'  => '/parser/json/to/example1/test',
            ],
            $result
        );

        $node->setType(TypeEnum::FTP);
        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
        self::assertEquals(
            [
                'process_path' => '/connector/ftp/action',
                'status_path'  => '/connector/ftp/action/test',
            ],
            $result
        );

        $node->setType(TypeEnum::EMAIL);
        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
        self::assertEquals(
            [
                'process_path' => '/mailer/email',
                'status_path'  => '/mailer/email/test',
            ],
            $result
        );

        $node->setType(TypeEnum::MAPPER);
        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
        self::assertEquals(
            [
                'process_path' => '/mapper/example1/process',
                'status_path'  => '/mapper/example1/test',
            ],
            $result
        );

        $node->setType(TypeEnum::CONNECTOR);
        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
        self::assertEquals(
            [
                'process_path' => '/connector/example1/webhook',
                'status_path'  => '/connector/example1/webhook/test',
            ],
            $result
        );

        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, FALSE]);
        self::assertEquals(
            [
                'process_path' => '/connector/example1/action',
                'status_path'  => '/connector/example1/action/test',
            ],
            $result
        );

        $node->setType(TypeEnum::SIGNAL);
        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
        self::assertEquals(
            [
                'process_path' => '/custom_node/signal/process',
                'status_path'  => '/custom_node/signal/process/test',
            ],
            $result
        );

        $node->setType(TypeEnum::USER);
        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
        self::assertEquals(
            [
                'process_path' => '/longRunning/example1/process',
                'status_path'  => '/longRunning/example1/process/test',
            ],
            $result
        );

        $node->setType(TypeEnum::API);
        $result = $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
        self::assertEquals(
            [
                'process_path' => '/connector/api/action',
                'status_path'  => '/connector/api/action/test',
            ],
            $result
        );

        $node->setType(TypeEnum::GATEWAY);
        self::expectException(TopologyConfigException::class);
        $this->invokeMethod($configFactory, 'getPaths', [$node, TRUE]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getHost
     *
     * @throws Exception
     */
    public function testGetHost(): void
    {
        $configFactory = self::$container->get('hbpf.topology.configurator');

        $result = $this->invokeMethod($configFactory, 'getHost', [TypeEnum::XML_PARSER, NULL]);
        self::assertEquals('xml-parser-api', $result);

        $result = $this->invokeMethod($configFactory, 'getHost', [TypeEnum::FTP, NULL]);
        self::assertEquals('ftp-api', $result);

        $result = $this->invokeMethod($configFactory, 'getHost', [TypeEnum::EMAIL, NULL]);
        self::assertEquals('mailer-api', $result);

        $result = $this->invokeMethod($configFactory, 'getHost', [TypeEnum::MAPPER, NULL]);
        self::assertEquals('mapper-api', $result);

        $result = $this->invokeMethod($configFactory, 'getHost', [TypeEnum::SIGNAL, NULL]);
        self::assertEquals('127.0.0.2', $result);

        $dto    = new SystemConfigDto('host');
        $result = $this->invokeMethod($configFactory, 'getHost', [TypeEnum::SIGNAL, $dto]);
        self::assertEquals('host', $result);

        self::expectException(TopologyConfigException::class);
        $this->invokeMethod($configFactory, 'getHost', ['something', new SystemConfigDto()]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getPort
     *
     * @throws Exception
     */
    public function testGetPort(): void
    {
        $configFactory = self::$container->get('hbpf.topology.configurator');

        $result = $this->invokeMethod($configFactory, 'getPort', [TypeEnum::FTP]);
        self::assertEquals(80, $result);

        self::expectException(TopologyConfigException::class);
        $this->invokeMethod($configFactory, 'getPort', ['something']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getNextNode
     *
     * @throws Exception
     */
    public function testGetNextNode(): void
    {
        $configFactory = self::$container->get('hbpf.topology.configurator');

        $result = $this->invokeMethod($configFactory, 'getNextNode', [new Node()]);
        self::assertNull($result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyManager::makePatchRequestForCron
     *
     * @throws Exception
     */
    public function testPatchRequestForCron(): void
    {
        $sender = self::createPartialMock(CurlManager::class, ['send']);
        $sender->expects(self::any())->method('send')->willThrowException(new CurlException());
        self::$container->set('hbpf.transport.curl_manager', $sender);

        $manager = self::$container->get('hbpf.configurator.manager.topology');

        $topology = new Topology();
        $this->pfd($topology);

        $node = (new Node())->setType('api')->setTopology($topology->getId());
        $this->pfd($node);

        self::expectException(TopologyException::class);
        $this->invokeMethod($manager, 'makePatchRequestForCron', [$node, 'cron', '1']);
    }

}
