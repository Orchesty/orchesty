<?php declare(strict_types=1);

namespace Tests\Unit\Utils;

use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Model\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Utils\TopologyConfigFactory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologyConfigFactoryTest
 *
 * @package Tests\Unit\Utils
 */
final class TopologyConfigFactoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws EnumException
     * @throws NodeException
     */
    public function testCreate(): void
    {
        $settings = new SystemConfigDto('someSdkHost', '', 10);

        $node1 = (new Node())->setTopology('123')->setType(TypeEnum::XML_PARSER);
        $node2 = (new Node())->setTopology('123')->setSystemConfigs($settings)->setType(TypeEnum::USER);
        $node3 = (new Node())->setTopology('123')->setType(TypeEnum::SIGNAL);

        $this->persistAndFlush($node1);
        $this->persistAndFlush($node2);
        $this->persistAndFlush($node3);

        /** @var NodeRepository $nodeRepository */
        $nodeRepository = $this->dm->getRepository(Node::class);
        $nodes          = $nodeRepository->getNodesByTopology('123');

        $result = TopologyConfigFactory::create($nodes);
        self::assertIsString($result);

        $repository = $this->dm->getRepository(Node::class);
        $nodes      = $repository->findAll();

        $arr = json_decode($result, TRUE);
        self::assertArrayNotHasKey(TopologyConfigFactory::WORKER, $arr);
        self::assertArrayNotHasKey(TopologyConfigFactory::SETTINGS, $arr);

        self::assertEquals('monolith-api',
            $arr[TopologyConfigFactory::NODE_CONFIG][$nodes[1]->getId()][TopologyConfigFactory::WORKER][TopologyConfigFactory::SETTINGS][TopologyConfigFactory::HOST]
        );
        self::assertEquals(3, count($arr[TopologyConfigFactory::NODE_CONFIG]));

        $node4 = (new Node())->setTopology('123')->setType(TypeEnum::BATCH);
        $this->persistAndFlush($node4);
        $nodes = $nodeRepository->getNodesByTopology('123');

        self::expectException(Exception::class);
        TopologyConfigFactory::create($nodes);
    }

}