<?php declare(strict_types=1);

namespace Tests\Integration\LongRunningNode\Model;

use Exception;
use Hanaboso\CommonsBundle\Metrics\InfluxDbSender;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\StartingPoint\Headers;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeManager;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeStartingPoint;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class LongRunningDataStartingPointTest
 *
 * @package Tests\Integration\LongRunningNode\Model
 */
final class LongRunningDataStartingPointTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @covers LongRunningNodeStartingPoint::run()
     *
     * @throws Exception
     */
    public function testHeaders(): void
    {
        $sp  = $this->mockStartingPoint();
        $top = new Topology();
        $top->setName('top');
        $node = new Node();
        $node->setName('node');
        $this->dm->persist($top);
        $this->dm->persist($node);
        $this->dm->flush();

        $sp->run($top, $node, 'body');
    }

    /**
     * @return LongRunningNodeStartingPoint
     */
    private function mockStartingPoint(): LongRunningNodeStartingPoint
    {
        /** @var BunnyManager|MockObject $bunny */
        $bunny = $this->createMock(BunnyManager::class);
        /** @var CurlManagerInterface|MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        /** @var InfluxDbSender|MockObject $sender */
        $sender = $this->createMock(InfluxDbSender::class);

        $doc = new LongRunningNodeData();
        $doc->setProcessId('proc')
            ->setUpdatedBy('usr')
            ->setAuditLogs(['log']);
        $this->dm->persist($doc);
        $this->dm->flush();

        /** @var LongRunningNodeManager|MockObject $manager */
        $manager = $this->createMock(LongRunningNodeManager::class);
        $manager->expects($this->once())->method('getDocument')->willReturn($doc);

        /** @var LongRunningNodeStartingPoint|MockObject $doc */
        $sp = $this->getMockBuilder(LongRunningNodeStartingPoint::class)
            ->setMethods(['runTopology'])
            ->setConstructorArgs([$bunny, $curl, $sender, $manager])
            ->getMock();
        $sp->expects($this->once())->method('runTopology')->willReturnCallback(
            function (Topology $topology, Node $node, Headers $headers, ?string $body = NULL): void {
                $topology;
                $node;
                $body;
                $tmp = $headers->getHeaders();

                self::assertEquals([
                    'pf-parent-id'           => '',
                    'pf-sequence-id'         => '1',
                    'pf-topology-id'         => $tmp['pf-topology-id'],
                    'pf-topology-name'       => 'top',
                    'content-type'           => 'application/json',
                    'timestamp'              => $tmp['timestamp'],
                    'pf-published-timestamp' => $tmp['pf-published-timestamp'],
                    'pf-process-id'          => $tmp['pf-process-id'],
                    'pf-correlation-id'      => $tmp['pf-correlation-id'],
                    'pf-doc-id'              => $tmp['pf-doc-id'],
                    'delivery-mode'          => 1,
                ], $headers->getHeaders());
            }
        );

        /** @var LongRunningNodeStartingPoint $tmp */
        $tmp = $sp;

        return $tmp;
    }

}