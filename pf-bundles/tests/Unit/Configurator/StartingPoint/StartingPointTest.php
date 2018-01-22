<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 2:59 PM
 */

namespace Tests\Unit\Configurator\StartingPoint;

use Bunny\Channel;
use Exception;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\StartingPointException;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Tests\PrivateTrait;

/**
 * Class StartingPointTest
 *
 * @package Tests\Unit\Configurator\StartingPoint
 */
final class StartingPointTest extends TestCase
{

    use PrivateTrait;

    /**
     * @var BunnyManager|MockObject
     */
    private $bunnyManager;

    /**
     * @var Topology
     */
    private $topology;

    /**
     * @var Node
     */
    private $node;

    /**
     * @var CurlManagerInterface|MockObject
     */
    private $curlManager;

    /**
     * @var InfluxDbSender
     */
    private $influxDb;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $channel = $this->createMock(Channel::class);
        $channel->method('queueDeclare');

        $this->bunnyManager = $this->createMock(BunnyManager::class);
        $this->bunnyManager->method('getChannel')->willReturn($channel);

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

        $this->curlManager = $this->createMock(CurlManagerInterface::class);
        $this->influxDb    = $this->createMock(InfluxDbSender::class);
    }

    /**
     * @covers StartingPoint::run()
     * @throws Exception
     */
    public function testRun(): void
    {
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $startingPoint->run($this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::runWithRequest()
     * @throws Exception
     */
    public function testRunWithRequest(): void
    {
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $startingPoint->runWithRequest(Request::createFromGlobals(), $this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::validateTopology()
     * @throws Exception
     */
    public function testValidateBadNode(): void
    {
        $this->node->setTopology('999');

        $this->expectException(StartingPointException::class);
        $this->expectExceptionMessage('The node[id=1] does not belong to the topology[id=1].');
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $startingPoint->run($this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::validateTopology()
     * @throws Exception
     */
    public function testValidateEnableTopology(): void
    {
        $this->topology->setEnabled(FALSE);

        $this->expectException(StartingPointException::class);
        $this->expectExceptionMessage('The topology[id=1] does not enable.');
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $startingPoint->run($this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::validateTopology()
     * @throws Exception
     */
    public function testValidateEnableNode(): void
    {
        $this->node->setEnabled(FALSE);

        $this->expectException(StartingPointException::class);
        $this->expectExceptionMessage('The node[id=1] does not enable.');
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $startingPoint->run($this->topology, $this->node);
    }

    /**
     * @covers StartingPoint::createQueueName()
     */
    public function testCreateQueueName(): void
    {
        $name = StartingPoint::createQueueName($this->topology, $this->node);
        $this->assertSame('pipes.1-top.1-mag-cus', $name);
    }

    /**
     * @covers StartingPoint::createHeaders()
     */
    public function testCreateHeaders(): void
    {
        /** @var Topology|MockObject $topology */
        $topology = $this->createMock(Topology::class);
        $topology->method('getId')->willReturn('13');
        $topology->method('getName')->willReturn('name');
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $headers       = $startingPoint->createHeaders($topology);

        $this->assertCount(9, $headers->getHeaders());
        $this->assertArrayHasKey(PipesHeaders::PF_PREFIX . 'process-id', $headers->getHeaders());
        $this->assertArrayHasKey(PipesHeaders::PF_PREFIX . 'parent-id', $headers->getHeaders());
        $this->assertArrayHasKey(PipesHeaders::PF_PREFIX . 'correlation-id', $headers->getHeaders());
        $this->assertArrayHasKey(PipesHeaders::PF_PREFIX . 'sequence-id', $headers->getHeaders());
        $this->assertArrayHasKey(PipesHeaders::PF_PREFIX . 'topology-id', $headers->getHeaders());
        $this->assertArrayHasKey(PipesHeaders::PF_PREFIX . 'topology-name', $headers->getHeaders());
        $this->assertArrayHasKey('content-type', $headers->getHeaders());
        $this->assertArrayHasKey('timestamp', $headers->getHeaders());
        $this->assertArrayHasKey(PipesHeaders::PF_PREFIX . 'published-timestamp', $headers->getHeaders());
    }

    /**
     * @covers StartingPoint::createBodyFromRequest()
     */
    public function testCreateBodyFromRequestXml(): void
    {
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);

        $request = new Request([], [], [], [], [], [
            'CONTENT_TYPE' => 'application/xml',
        ], '
<?xml version="1.0" encoding="UTF-8" ?>
<!-- Comment -->
<root attr="Name">
  <title>Title</title>
</root>'
        );
        $request->setMethod('POST');

        $body = $startingPoint->createBodyFromRequest($request);

        $this->assertSame('
<?xml version="1.0" encoding="UTF-8" ?>
<!-- Comment -->
<root attr="Name">
  <title>Title</title>
</root>', $body);
    }

    /**
     * @covers StartingPoint::createBodyFromRequest()
     */
    public function testCreateBodyFromRequestJson(): void
    {
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);

        $request = new Request([], [], [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ],
            '{"name": "Name", "array": []}'
        );
        $request->setMethod('PUT');

        $body = $startingPoint->createBodyFromRequest($request);

        $this->assertSame('{"name": "Name", "array": []}', $body);
    }

    /**
     * @covers StartingPoint::createBodyFromRequest()
     */
    public function testCreateBodyFromRequestCsv(): void
    {
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);

        $request = new Request([], [], [], [], [], [
            'CONTENT_TYPE' => 'text/csv',
        ],
            'Data1,Data2,Data3'
        );
        $request->setMethod('POST');

        $body = $startingPoint->createBodyFromRequest($request);

        $this->assertSame("Data1,Data2,Data3", $body);
    }

    /**
     * @covers StartingPoint::createBody()
     */
    public function testCreateBodyEmpty(): void
    {
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $body          = $startingPoint->createBody();

        $this->assertSame("", $body);
    }

    /**
     * @covers StartingPoint::createBody()
     */
    public function testCreateBody(): void
    {
        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $body          = $startingPoint->createBody(json_encode(['param' => 'test']));

        $body = json_decode($body, TRUE);

        $this->assertSame(['param' => 'test'], $body);
    }

    /**
     * @covers StartingPoint::runTest()
     * @throws StartingPointException
     */
    public function testRunTest(): void
    {
        $responseBody = json_encode([
            'status'  => TRUE,
            'message' => '5/5 node ok',
            'nodes'   => [],
        ]);

        $this->curlManager->method('send')->willReturn(
            new ResponseDto(200, '', $responseBody, ['application/json'])
        );

        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $body          = $startingPoint->runTest($this->topology);

        $this->assertEquals(json_decode($responseBody, TRUE), $body);
    }

    /**
     * @covers StartingPoint::runTest()
     * @throws StartingPointException
     */
    public function testRunTestBadRequest(): void
    {
        $this->curlManager->method('send')->willReturn(
            new ResponseDto(400, 'Error', '', ['application/json'])
        );

        $startingPoint = new StartingPoint($this->bunnyManager, $this->curlManager, $this->influxDb);
        $this->expectException(StartingPointException::class);
        $this->expectExceptionMessage('Request error: Error');
        $startingPoint->runTest($this->topology);
    }

}