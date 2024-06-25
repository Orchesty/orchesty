<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Document;

use Exception;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Database\Document\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class NodeTest
 *
 * @package PipesFrameworkTests\Integration\Database\Document
 */
#[CoversClass(Node::class)]
final class NodeTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testNode(): void
    {
        $embedNode = (new EmbedNode())->setName('name');
        $this->invokeMethod($embedNode, 'setId', ['123']);
        $this->pfd($embedNode);

        $embedNode2 = (new EmbedNode())->setName('name2');
        $this->invokeMethod($embedNode2, 'setId', ['456']);
        $this->pfd($embedNode2);

        $node = (new Node())
            ->setTopology('123')
            ->setName('node')
            ->setNext([$embedNode])
            ->setHandler('action')
            ->setEnabled(TRUE)
            ->setSystemConfigs(new SystemConfigDto())
            ->setCronParams(NULL)
            ->setSchemaId('789')
            ->setType('mapper')
            ->setCron('cron');
        $node->addNext($embedNode2);
        $this->pfd($node);

        self::assertEquals('123', $node->getTopology());
        self::assertEquals('node', $node->getName());
        self::assertEquals('action', $node->getHandler());
        self::assertEquals('789', $node->getSchemaId());
        self::assertEquals('mapper', $node->getType());
        self::assertEquals('cron', $node->getCron());
        self::assertEquals(2, count($node->getNext()));
        self::assertInstanceOf(SystemConfigDto::class, $node->getSystemConfigs());
        self::assertNull($node->getCronParams());
        self::assertTrue($node->isEnabled());
    }

    /**
     * @throws Exception
     */
    public function testSetTypeErr(): void
    {
        $node = new Node();
        self::expectException(NodeException::class);
        $node->setType('mujType');
    }

    /**
     * @throws Exception
     */
    public function testSetHandler(): void
    {
        $node = new Node();
        self::expectException(NodeException::class);
        $node->setHandler('mujHandler');
    }

    /**
     * @throws Exception
     */
    public function testGetSystemConfigs(): void
    {
        $node = new Node();
        self::assertNull($node->getSystemConfigs());
    }

}
