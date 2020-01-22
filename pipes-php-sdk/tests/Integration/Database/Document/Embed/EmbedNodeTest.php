<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Database\Document\Embed;

use Exception;
use Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class EmbedNodeTest
 *
 * @package PipesPhpSdkTests\Integration\Database\Document\Embed
 */
final class EmbedNodeTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode::setName
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode::getId
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode::setId
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode::getName
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode::from
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
