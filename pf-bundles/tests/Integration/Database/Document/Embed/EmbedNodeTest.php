<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Document\Embed;

use Exception;
use Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Database\Document\Node;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class EmbedNodeTest
 *
 * @package PipesFrameworkTests\Integration\Database\Document\Embed
 */
final class EmbedNodeTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode::setName
     * @covers \Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode::getId
     * @covers \Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode::setId
     * @covers \Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode::getName
     * @covers \Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode::from
     *
     * @throws Exception
     */
    public function testNode(): void
    {
        $embedNode = (new EmbedNode())->setName('name');
        $this->invokeMethod($embedNode, 'setId', ['123']);
        $this->pfd($embedNode);

        $node = (new Node())->setName('node-name');
        $this->pfd($node);

        self::assertEquals('name', $embedNode->getName());
        self::assertEquals('123', $embedNode->getId());
        self::assertEquals('node-name', EmbedNode::from($node)->getName());
    }

}
