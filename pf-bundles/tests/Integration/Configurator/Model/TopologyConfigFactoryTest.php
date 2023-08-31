<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Model;

use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory;
use Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\Utils\String\Json;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyConfigFactoryTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Model
 */
final class TopologyConfigFactoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::create
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getEnvParameters
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::loopNodes
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::assembleNode
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getWorkers
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getWorkerByType
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getFaucet
     *
     * @throws Exception
     */
    public function testCreate(): void
    {
        $settings = new SystemConfigDto('someSdkHost', '', 10);

        $node1 = (new Node())->setTopology('123')->setType(TypeEnum::WEBHOOK->value)->setName('example1');
        $node2 = (new Node())->setTopology('123')->setName('example2')->setSystemConfigs($settings)
            ->setType(TypeEnum::CONNECTOR->value);
        $node3 = (new Node())->setTopology('123')->setName('example3')->setType(TypeEnum::BATCH->value);
        $node4 = (new Node())->setTopology('123')->setName('example4')->setType(TypeEnum::CONNECTOR->value);
        $node5 = (new Node())->setTopology('123')->setName('example5')->setType(TypeEnum::USER->value);

        $this->pfd($node1);
        $this->pfd($node2);

        $sdk = (new Sdk())->setName('name')->setHeaders(['key'=> 'value'])->setUrl('someSdkHost');
        $this->pfd($sdk);
        $sdk2 = (new Sdk())->setName('name2')->setHeaders(['key'=> 'value'])->setUrl('127.0.0.2');
        $this->pfd($sdk2);

        $embedNode = new EmbedNode();
        $embedNode->setName('embedNode2');
        $this->setProperty($embedNode, 'id', $node2->getId());
        $node1->addNext($embedNode);
        $this->pfd($node1);

        $this->pfd($embedNode);
        $this->pfd($node3);
        $this->pfd($node4);
        $this->pfd($node5);

        $nodeRepository = $this->dm->getRepository(Node::class);
        $nodes          = $nodeRepository->getNodesByTopology('123');

        $configFactory = self::getContainer()->get('hbpf.topology.configurator');
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
        $configFactory = self::getContainer()->get('hbpf.topology.configurator');
        $node          = (new Node())->setTopology('123')->setType(TypeEnum::CONNECTOR->value)->setName('example1');

        $result = $this->invokeMethod($configFactory, 'getWorkerByType', [$node]);
        self::assertEquals('worker.http', $result);

        $node->setType(TypeEnum::BATCH->value);
        $result = $this->invokeMethod($configFactory, 'getWorkerByType', [$node]);
        self::assertEquals('worker.batch', $result);

        $node->setType(TypeEnum::USER->value);
        $result = $this->invokeMethod($configFactory, 'getWorkerByType', [$node]);
        self::assertEquals('worker.user', $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getPaths
     *
     * @throws Exception
     */
    public function testGetPaths(): void
    {
        $configFactory = self::getContainer()->get('hbpf.topology.configurator');
        $node          = (new Node())->setTopology('123')->setType(TypeEnum::XML_PARSER->value)->setName('example1');

        $result = $this->invokeMethod($configFactory, 'getPaths', [$node]);
        self::assertEquals(
            [
                'process_path' => '/xml_parser',
                'status_path'  => '/xml_parser/test',
            ],
            $result,
        );

        $node->setType(TypeEnum::CONNECTOR->value);
        $result = $this->invokeMethod($configFactory, 'getPaths', [$node]);
        self::assertEquals(
            [
                'process_path' => '/connector/example1/action',
                'status_path'  => '/connector/example1/action/test',
            ],
            $result,
        );

        $node->setType(TypeEnum::GATEWAY->value);
        self::expectException(TopologyConfigException::class);
        $this->invokeMethod($configFactory, 'getPaths', [$node]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory::getHost
     *
     * @throws Exception
     */
    public function testGetHost(): void
    {
        $configFactory = self::getContainer()->get('hbpf.topology.configurator');

        $result = $this->invokeMethod($configFactory, 'getHost', [TypeEnum::CONNECTOR->value, NULL]);
        self::assertEquals('127.0.0.2', $result);

        $result = $this->invokeMethod($configFactory, 'getHost', [TypeEnum::BATCH->value, NULL]);
        self::assertEquals('127.0.0.2', $result);

        $result = $this->invokeMethod($configFactory, 'getHost', [TypeEnum::USER->value, NULL]);
        self::assertEquals('', $result);

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
        $configFactory = self::getContainer()->get('hbpf.topology.configurator');

        $result = $this->invokeMethod($configFactory, 'getPort', [TypeEnum::CONNECTOR->value]);
        self::assertEquals(80, $result);

        self::expectException(TopologyConfigException::class);
        $this->invokeMethod($configFactory, 'getPort', ['something']);
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
        self::getContainer()->set('hbpf.transport.curl_manager', $sender);

        $manager = self::getContainer()->get('hbpf.configurator.manager.topology');

        $topology = new Topology();
        $this->pfd($topology);

        $node = (new Node())->setType('api')->setTopology($topology->getId());
        $this->pfd($node);

        self::expectException(TopologyException::class);
        $this->invokeMethod($manager, 'makePatchRequestForCron', [$node, 'cron', '1']);
    }

}
