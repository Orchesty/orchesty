<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Repository;

use Exception;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Reduction\NodeReduction;
use LogicException;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class NodeRepositoryTest
 *
 * @package PipesFrameworkTests\Integration\Database\Repository
 */
final class NodeRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\NodeRepository::getEventNodesByTopology
     *
     * @throws Exception
     */
    public function testGetEventNodesByTopology(): void
    {
        $repo = $this->dm->getRepository(Node::class);

        $result = $repo->getEventNodesByTopology('abc123');

        self::assertEmpty($result);

        $node1 = new Node();
        $node1->setName('name 1');
        $node1->setType(TypeEnum::CONNECTOR->value);
        $node1->setTopology('abc123');

        $node2 = new Node();
        $node2->setName('name 2');
        $node2->setType(TypeEnum::EMAIL->value);
        $node2->setTopology('abc123');

        $this->dm->persist($node1);
        $this->dm->persist($node2);
        $this->dm->flush();

        NodeReduction::$typeExclude = [];
        $result                     = array_values($repo->getEventNodesByTopology('abc123'));
        self::assertCount(2, $result);
        self::assertEquals($node1, $result[0]);
        self::assertEquals($node2, $result[1]);

        NodeReduction::$typeExclude = [TypeEnum::EMAIL->value];
        $result                     = array_values($repo->getEventNodesByTopology('abc123'));
        self::assertCount(1, $result);
        self::assertEquals($node1, $result[0]);

        NodeReduction::$typeExclude = [TypeEnum::CONNECTOR->value];
        $result                     = array_values($repo->getEventNodesByTopology('abc123'));
        self::assertCount(1, $result);
        self::assertEquals($node2, $result[0]);

        NodeReduction::$typeExclude = [TypeEnum::EMAIL->value, TypeEnum::CONNECTOR->value];
        $result                     = array_values($repo->getEventNodesByTopology('abc123'));
        self::assertCount(0, $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\NodeRepository::getNodeByTopology
     *
     * @throws Exception
     */
    public function testGetNodeByTopology(): void
    {
        $repo   = $this->dm->getRepository(Node::class);
        $result = $repo->getNodeByTopology('name1', 'abc123');
        self::assertEmpty($result);

        $node1 = new Node();
        $node1
            ->setEnabled(TRUE)
            ->setName('name1')
            ->setTopology('abc123');

        $this->dm->persist($node1);
        $this->dm->flush();

        $result = $repo->getNodeByTopology('name1', 'abc123');
        self::assertNotEmpty($result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\NodeRepository::getStartingNode
     *
     * @throws Exception
     */
    public function testGetStartingPoint(): void
    {
        $repo = $this->dm->getRepository(Node::class);

        $topology = new Topology();
        $this->dm->persist($topology);
        $this->dm->flush();

        $node = new Node();
        $node
            ->setEnabled(TRUE)
            ->setTopology($topology->getId())
            ->setType(TypeEnum::SIGNAL->value)
            ->setHandler(HandlerEnum::EVENT->value);
        $this->dm->persist($node);
        $this->dm->flush();
        $this->dm->clear();

        self::assertEquals($node->getId(), $repo->getStartingNode($topology)->getId());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\NodeRepository::getStartingNode
     *
     * @throws Exception
     */
    public function testGetStartingPointNotFound(): void
    {
        $repo = $this->dm->getRepository(Node::class);

        $topology = new Topology();
        $this->dm->persist($topology);
        $this->dm->flush();

        $node = new Node();
        $node
            ->setEnabled(TRUE)
            ->setTopology($topology->getId())
            ->setType(TypeEnum::MAPPER->value)
            ->setHandler(HandlerEnum::EVENT->value);
        $this->dm->persist($node);
        $this->dm->flush();
        $this->dm->clear();

        self::expectException(LogicException::class);
        self::expectExceptionMessage(sprintf('Starting Node not found for topology [%s]', $topology->getId()));
        $repo->getStartingNode($topology);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\NodeRepository::getCronNodes
     * @covers \Hanaboso\PipesFramework\Database\Repository\NodeRepository::getTopologyType
     *
     * @throws Exception
     */
    public function testGetTopologyType(): void
    {
        $repo = $this->dm->getRepository(Node::class);

        $topology = new Topology();
        $this->dm->persist($topology);
        $this->dm->flush();

        $node = new Node();
        $node
            ->setEnabled(TRUE)
            ->setTopology($topology->getId())
            ->setType(TypeEnum::CRON->value)
            ->setHandler(HandlerEnum::EVENT->value);
        $this->dm->persist($node);
        $this->dm->flush();
        $this->dm->clear();

        $type = $repo->getTopologyType($topology);
        self::assertEquals(TypeEnum::CRON->value, $type);

        $topology = new Topology();
        $this->dm->persist($topology);
        $this->dm->flush();

        $node = new Node();
        $node
            ->setEnabled(TRUE)
            ->setTopology($topology->getId())
            ->setType(TypeEnum::CONNECTOR->value)
            ->setHandler(HandlerEnum::EVENT->value);
        $this->dm->persist($node);
        $this->dm->flush();
        $this->dm->clear();

        $type = $repo->getTopologyType($topology);
        self::assertEquals(TypeEnum::WEBHOOK->value, $type);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\NodeRepository::getNodesByTopology
     *
     * @throws Exception
     */
    public function testGetNodesByTopology(): void
    {
        $repo = $this->dm->getRepository(Node::class);

        $topology = new Topology();
        $this->dm->persist($topology);
        $this->dm->flush();

        $node = new Node();
        $node
            ->setEnabled(TRUE)
            ->setTopology($topology->getId())
            ->setType(TypeEnum::MAPPER->value)
            ->setHandler(HandlerEnum::EVENT->value);
        $this->dm->persist($node);
        $this->dm->flush();
        $this->dm->clear();

        $nodes = $repo->getNodesByTopology($topology->getId());
        self::assertCount(1, $nodes);
        /** @var Node $first */
        $first = reset($nodes);
        self::assertEquals($node->getId(), $first->getId());
    }

}
