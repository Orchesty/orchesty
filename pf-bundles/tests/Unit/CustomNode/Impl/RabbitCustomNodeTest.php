<?php declare(strict_types=1);

namespace Tests\Unit\CustomNode\Impl;

use Bunny\Channel;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\CustomNode\Impl\RabbitCustomNode;
use Hanaboso\PipesFramework\CustomNode\Model\Batch\BatchProducer;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class RabbitCustomNodeTest
 *
 * @package Tests\Unit\CustomNode\Impl
 */
class RabbitCustomNodeTest extends KernelTestCaseAbstract
{

    /**
     * @covers RabbitCustomNode::process()
     */
    public function testRabbitNode(): void
    {
        $dto = new ProcessDto();
        $dto->setData('{"count":2}')
            ->setHeaders([
                'pf-topology-id' => 'top',
                'pf-node-id'     => 'node',
            ]);

        $node = new RabbitCustomNode($this->mockDm(), $this->mockProducer());
        $node->process($dto);
    }

    /**
     * @return DocumentManager
     */
    private function mockDm(): DocumentManager
    {
        /** @var EmbedNode|PHPUnit_Framework_MockObject_MockObject $next1 */
        $next1 = $this->createPartialMock(EmbedNode::class, ['getId']);
        $next1->method('getId')->willReturn('node1');
        $next1->setName('node1');

        /** @var EmbedNode|PHPUnit_Framework_MockObject_MockObject $next2 */
        $next2 = $this->createPartialMock(EmbedNode::class, ['getId']);
        $next2->method('getId')->willReturn('node2');
        $next2->setName('node1');

        /** @var Node|PHPUnit_Framework_MockObject_MockObject $node */
        $node = $this->createPartialMock(Node::class, ['getId', 'getNext']);
        $node->method('getId')->willReturn('node0');
        $node->method('getNext')->willReturn([$next1, $next2]);

        /** @var NodeRepository|PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(NodeRepository::class);
        $repo->expects($this->once())
            ->method('find')->willReturn($node);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return AbstractProducer
     */
    private function mockProducer(): AbstractProducer
    {
        /** @var Channel|PHPUnit_Framework_MockObject_MockObject $chann */
        $chann = $this->createMock(Channel::class);
        $chann->method('queueBind')->willReturn(TRUE);
        $chann->expects($this->at(2))->method('publish')->willReturnCallback(
            function (string $msg, array $headers, string $ex, string $que): void {
                self::assertEquals('{"BenchmarkTotal": 2, "BenchmarkNumber": 0}', $msg);
                self::assertEquals('pipes.top.node1-nod', $que);
            }
        );
        $chann->expects($this->at(3))->method('publish')->willReturnCallback(
            function (string $msg, array $headers, string $ex, string $que): void {
                self::assertEquals('{"BenchmarkTotal": 2, "BenchmarkNumber": 0}', $msg);
                self::assertEquals('pipes.top.node2-nod', $que);
            }
        );
        $chann->expects($this->at(4))->method('publish')->willReturnCallback(
            function (string $msg, array $headers, string $ex, string $que): void {
                self::assertEquals('{"BenchmarkTotal": 2, "BenchmarkNumber": 1}', $msg);
                self::assertEquals('pipes.top.node1-nod', $que);
            }
        );
        $chann->expects($this->at(5))->method('publish')->willReturnCallback(
            function (string $msg, array $headers, string $ex, string $que): void {
                self::assertEquals('{"BenchmarkTotal": 2, "BenchmarkNumber": 1}', $msg);
                self::assertEquals('pipes.top.node2-nod', $que);
            }
        );

        /** @var BunnyManager|PHPUnit_Framework_MockObject_MockObject $man */
        $man = $this->createMock(BunnyManager::class);
        $man->method('getChannel')->willReturn($chann);

        /** @var BatchProducer|PHPUnit_Framework_MockObject_MockObject $prod */
        $prod = $this->createMock(BatchProducer::class);
        $prod->method('getExchange')->willReturn('ex');
        $prod->method('getManager')->willReturn($man);

        return $prod;
    }

}