<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Document\Embed;

use Exception;
use Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Database\Document\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class EmbedNodeTest
 *
 * @package PipesFrameworkTests\Integration\Database\Document\Embed
 */
#[CoversClass(EmbedNode::class)]
final class EmbedNodeTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testNode(): void
    {
        $embedNode = (new EmbedNode())->setName('name');
        $this->invokeMethod($embedNode, 'setId', ['123']);
        $this->pfd($embedNode);

        $node = (new Node())->setName('node-name');
        $this->pfd($node);

        self::assertSame('name', $embedNode->getName());
        self::assertSame('123', $embedNode->getId());
        self::assertSame('node-name', EmbedNode::from($node)->getName());
    }

}
