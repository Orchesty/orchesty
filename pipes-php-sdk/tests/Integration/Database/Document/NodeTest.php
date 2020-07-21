<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Database\Document;

use Exception;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesPhpSdk\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class NodeTest
 *
 * @package PipesPhpSdkTests\Integration\Database\Document
 */
final class NodeTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getSchemaId
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getName
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setName
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getTopology
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getNext
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::addNext
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setNext
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getType
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setType
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getHandler
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setHandler
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::isEnabled
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setEnabled
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getCron
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setCron
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getCronParams
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setCronParams
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setSystemConfigs
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getSystemConfigs
     *
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
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setType
     *
     * @throws Exception
     */
    public function testSetTypeErr(): void
    {
        $node = new Node();
        self::expectException(NodeException::class);
        $node->setType('mujType');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::setHandler
     *
     * @throws Exception
     */
    public function testSetHandler(): void
    {
        $node = new Node();
        self::expectException(NodeException::class);
        $node->setHandler('mujHandler');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Node::getSystemConfigs
     *
     * @throws Exception
     */
    public function testGetSystemConfigs(): void
    {
        $node = new Node();
        self::assertNull($node->getSystemConfigs());
    }

}
