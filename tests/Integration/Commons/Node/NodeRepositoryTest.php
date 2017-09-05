<?php declare(strict_types=1);

namespace Tests\Integration\Commons\Node;

use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\Node\NodeRepository;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class NodeRepositoryTest
 *
 * @package Tests\Integration\Commons\Node
 */
final class NodeRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers NodeRepository::getEventNodesByTopology()
     */
    public function testGetEventNodesByTopology(): void
    {
        /** @var NodeRepository $repo */
        $repo = $this->dm->getRepository(Node::class);

        $topology = new Topology();
        $topology->setName('topology name');

        $this->dm->persist($topology);
        $this->dm->flush();

        $result = $repo->getEventNodesByTopology($topology->getId());

        self::assertEquals(NULL, $result[0]);

        $node1 = new Node();
        $node1->setName('name 1');
        $node1->setHandler(HandlerEnum::EVENT);
        $node1->setTopology($topology->getId());

        $node2 = new Node();
        $node2->setName('name 2');
        $node2->setHandler(HandlerEnum::ACTION);
        $node2->setTopology($topology->getId());

        $this->dm->persist($node1);
        $this->dm->persist($node2);
        $this->dm->flush();

        $node1->setHandler(HandlerEnum::EVENT);
        $this->dm->flush();

        $result = $repo->getEventNodesByTopology($topology->getId());

        self::assertCount(1, $result);
        self::assertEquals($node1, $result[0]);
    }

    /**
     * @covers NodeRepository::getNodeByTopology()
     */
    public function testGetNodeByTopology(): void
    {

    }

}