<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 2:59 PM
 */

namespace Tests\Unit\Commons\StartingPoint;

use Bunny\Channel;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\StartingPoint\Exception\StartingPointException;
use Hanaboso\PipesFramework\Commons\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\Commons\StartingPoint\StartingPointProducer;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\RabbitMqBundle\BunnyManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use Tests\PrivateTrait;

/**
 * Class StartingPointTest
 *
 * @package Tests\Unit\Commons\StartingPoint
 */
class StartingPointTest extends TestCase
{

    use PrivateTrait;

    /**
     * @var StartingPointProducer|PHPUnit_Framework_MockObject_MockObject
     */
    private $startingPointProducer;

    /**
     * @var Topology
     */
    private $topology;

    /**
     * @var Node
     */
    private $node;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $channel = $this->createMock(Channel::class);
        $channel->method('queueDeclare');

        $bunnyManager = $this->createMock(BunnyManager::class);
        $bunnyManager->method('getChannel')->willReturn($channel);

        $this->startingPointProducer = $this->createMock(StartingPointProducer::class);
        $this->startingPointProducer->method('getManager')->willReturn($bunnyManager);

        $this->topology = new Topology();
        $this->setProperty($this->topology, 'id', '1');
        $this->topology
            ->setName('topology')
            ->setEnabled(TRUE);

        $this->node = new Node();
        $this->setProperty($this->node, 'id', '1');
        $this->node
            ->setName('magento2_customer')
            ->setTopology($this->topology->getId())
            ->setEnabled(TRUE);
    }

    /**
     * @covers StartingPoint::run()
     */
    public function testRun(): void
    {
        $startingPoint = new StartingPoint($this->startingPointProducer);
        $startingPoint->run($this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::runWithRequest()
     */
    public function testRunWithRequest(): void
    {
        $startingPoint = new StartingPoint($this->startingPointProducer);
        $startingPoint->runWithRequest(Request::createFromGlobals(), $this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::validateTopology()
     */
    public function testValidateBadNode(): void
    {
        $this->node->setTopology('999');

        $this->expectException(StartingPointException::class);
        $this->expectExceptionMessage('The node[id=1] does not belong to the topology[id=1].');
        $startingPoint = new StartingPoint($this->startingPointProducer);
        $startingPoint->run($this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::validateTopology()
     */
    public function testValidateEnableTopology(): void
    {
        $this->topology->setEnabled(FALSE);

        $this->expectException(StartingPointException::class);
        $this->expectExceptionMessage('The topology[id=1] does not enable.');
        $startingPoint = new StartingPoint($this->startingPointProducer);
        $startingPoint->run($this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::validateTopology()
     */
    public function testValidateEnableNode(): void
    {
        $this->node->setEnabled(FALSE);

        $this->expectException(StartingPointException::class);
        $this->expectExceptionMessage('The node[id=1] does not enable.');
        $startingPoint = new StartingPoint($this->startingPointProducer);
        $startingPoint->run($this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::createQueueName()
     */
    public function testCreateQueueName(): void
    {
        $startingPoint = new StartingPoint($this->startingPointProducer);
        $name          = $startingPoint->createQueueName($this->topology, $this->node);
        $this->assertSame('pipes.1-topology.1-magento2-customer', $name);
    }

    /**
     * @covers StartingPoint::createHeaders()
     */
    public function testCreateHeaders(): void
    {
        $startingPoint = new StartingPoint($this->startingPointProducer);
        $headers       = $startingPoint->createHeaders();

        $this->assertCount(2, $headers->getHeaders());
        $this->assertArrayHasKey('job_id', $headers->getHeaders());
        $this->assertArrayHasKey('sequence_id', $headers->getHeaders());
    }

    /**
     * @covers StartingPoint::createBodyFromRequest()
     */
    public function testCreateBodyFromRequestXml(): void
    {
        $startingPoint = new StartingPoint($this->startingPointProducer);

        $request = new Request([], [], [], [], [], [
            'CONTENT_TYPE' => 'application/xml',
        ], '
<?xml version="1.0" encoding="UTF-8" ?>
<!-- Comment -->
<root attr="Name">
  <title>Title</title>
</root>'
        );

        $body = $startingPoint->createBodyFromRequest($request);

        $body = json_decode($body, TRUE);

        $this->assertSame([
            "data"     => '
<?xml version="1.0" encoding="UTF-8" ?>
<!-- Comment -->
<root attr="Name">
  <title>Title</title>
</root>',
            "settings" => "",
        ], $body);
    }

    /**
     * @covers StartingPoint::createBodyFromRequest()
     */
    public function testCreateBodyFromRequestJson(): void
    {
        $startingPoint = new StartingPoint($this->startingPointProducer);

        $request = new Request([], [], [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ],
            '{"name": "Name", "array": []}'
        );

        $body = $startingPoint->createBodyFromRequest($request);

        $body = json_decode($body, TRUE);

        $this->assertSame([
            "data"     => [
                "name"  => "Name",
                "array" => [],
            ],
            "settings" => "",
        ], $body);
    }

    /**
     * @covers StartingPoint::createBodyFromRequest()
     */
    public function testCreateBodyFromRequestCsv(): void
    {
        $startingPoint = new StartingPoint($this->startingPointProducer);

        $request = new Request([], [], [], [], [], [
            'CONTENT_TYPE' => 'text/csv',
        ],
            'Data1,Data2,Data3'
        );

        $body = $startingPoint->createBodyFromRequest($request);

        $body = json_decode($body, TRUE);

        $this->assertSame([
            "data"     => "Data1,Data2,Data3",
            "settings" => "",
        ], $body);
    }

    /**
     * @covers StartingPoint::createBody()
     */
    public function testCreateBody(): void
    {
        $startingPoint = new StartingPoint($this->startingPointProducer);
        $body          = $startingPoint->createBody();

        $body = json_decode($body, TRUE);

        $this->assertSame([
            "data"     => "",
            "settings" => "",
        ], $body);
    }

}