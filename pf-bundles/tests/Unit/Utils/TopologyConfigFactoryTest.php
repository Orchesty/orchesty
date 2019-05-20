<?php declare(strict_types=1);

namespace Tests\Unit\Utils;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Model\Dto\SystemConfigDto;
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
     * @covers TopologyConfigFactory::create()
     */
    public function testCreate(): void
    {
        $settings = new SystemConfigDto('someSdkHost', '', 10);

        $node1 = (new Node())->setTopology('123');
        $node2 = (new Node())->setTopology('123')->setSystemConfigs($settings);

        $this->persistAndFlush($node1);
        $this->persistAndFlush($node2);

        $nodeRepository = $this->dm->getRepository(Node::class);
        $nodes = $nodeRepository->getNodesByTopology('123');

        $result = TopologyConfigFactory::create($nodes);
        self::assertIsString($result);
    }

}