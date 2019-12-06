<?php declare(strict_types=1);

namespace Tests\Integration\Configurator\Model;

use Exception;
use Hanaboso\CommonsBundle\Database\Document\Dto\SystemConfigDto;
use Hanaboso\CommonsBundle\Database\Document\Embed\EmbedNode;
use Hanaboso\CommonsBundle\Database\Document\Node;
use Hanaboso\CommonsBundle\Database\Repository\NodeRepository;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesFramework\Configurator\Model\TopologyConfigFactory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologyConfigFactoryTest
 *
 * @package Tests\Integration\Configurator\Model
 */
class TopologyConfigFactoryTest extends DatabaseTestCaseAbstract
{

    /**
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

        $this->persistAndFlush($node1);
        $this->persistAndFlush($node2);

        $embedNode = new EmbedNode();
        $embedNode->setName('embedNode2');
        $this->setProperty($embedNode, 'id', $node2->getId());
        $node1->addNext($embedNode);
        $this->persistAndFlush($node1);

        $this->persistAndFlush($embedNode);
        $this->persistAndFlush($node3);
        $this->persistAndFlush($node4);
        $this->persistAndFlush($node5);

        /** @var NodeRepository $nodeRepository */
        $nodeRepository = $this->dm->getRepository(Node::class);
        $nodes          = $nodeRepository->getNodesByTopology('123');

        $configFactory = self::$container->get('hbpf.topology.configurator');
        $result        = $configFactory->create($nodes);

        self::assertIsString($result);
        $arr = Json::decode($result);

        self::assertArrayNotHasKey(TopologyConfigFactory::WORKER, $arr);
        self::assertArrayNotHasKey(TopologyConfigFactory::SETTINGS, $arr);

        $this->assertResult(__DIR__ . '/data/topologyConfigFactory.json', $arr);
        self::assertEquals(5, count($arr[TopologyConfigFactory::NODE_CONFIG]));
    }

}
