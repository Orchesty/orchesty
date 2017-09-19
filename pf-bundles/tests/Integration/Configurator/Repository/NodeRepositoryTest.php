<?php declare(strict_types=1);

namespace Tests\Integration\Configurator\Repository;

use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Reduction\NodeReduction;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class NodeRepositoryTest
 *
 * @package Tests\Integration\Configurator\Repository
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

        $result = $repo->getEventNodesByTopology('abc123');

        self::assertEmpty($result);

        $node1 = new Node();
        $node1->setName('name 1');
        $node1->setType(TypeEnum::CONNECTOR);
        $node1->setTopology('abc123');

        $node2 = new Node();
        $node2->setName('name 2');
        $node2->setType(TypeEnum::EMAIL);
        $node2->setTopology('abc123');

        $this->dm->persist($node1);
        $this->dm->persist($node2);
        $this->dm->flush();

        NodeReduction::$typeExclude = [];
        $result                     = array_values($repo->getEventNodesByTopology('abc123'));
        self::assertCount(2, $result);
        self::assertEquals($node1, $result[0]);
        self::assertEquals($node2, $result[1]);

        NodeReduction::$typeExclude = [TypeEnum::EMAIL];
        $result                     = array_values($repo->getEventNodesByTopology('abc123'));
        self::assertCount(1, $result);
        self::assertEquals($node1, $result[0]);

        NodeReduction::$typeExclude = [TypeEnum::CONNECTOR];
        $result                     = array_values($repo->getEventNodesByTopology('abc123'));
        self::assertCount(1, $result);
        self::assertEquals($node2, $result[0]);

        NodeReduction::$typeExclude = [TypeEnum::EMAIL, TypeEnum::CONNECTOR];
        $result                     = array_values($repo->getEventNodesByTopology('abc123'));
        self::assertCount(0, $result);
    }

}