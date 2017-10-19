<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 12.10.17
 * Time: 11:24
 */

namespace Tests\Unit\TopologyGenerator\Actions;

use Hanaboso\PipesFramework\Commons\Docker\Handler\DockerHandler;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Handler\RabbitMqHandler;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\DestroyTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\DockerComposeCli;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\PrivateTrait;

/**
 * Class DestroyTopologyActionsTest
 *
 * @package Tests\Unit\TopologyGenerator\Actions
 */
class DestroyTopologyActionsTest extends TestCase
{

    use PrivateTrait;

    /**
     * @var DockerHandler|PHPUnit_Framework_MockObject_MockObject
     */
    protected $dockerHandler;

    /**
     * @var RabbitMqHandler|PHPUnit_Framework_MockObject_MockObject
     */
    protected $rabbitMqHandler;

    /**
     * @var DestroyTopologyActions|PHPUnit_Framework_MockObject_MockObject
     */
    protected $actions;

    /**
     * @var DockerComposeCli|PHPUnit_Framework_MockObject_MockObject
     */
    protected $dockerComposeCli;

    /**
     * @var string
     */
    protected $configDir;

    /**
     * setUp
     */
    public function setUp(): void
    {
        $this->configDir = '/opt/srv/topology';

        $this->dockerHandler = $this->getMockBuilder(DockerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rabbitMqHandler = $this->getMockBuilder(RabbitMqHandler::class)
            ->setMethods(['deleteQueues', 'deleteExchange'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->actions = $this
            ->getMockBuilder(DestroyTopologyActions::class)
            ->setConstructorArgs([$this->dockerHandler, $this->rabbitMqHandler])
            ->setMethods(['getDockerComposeCli'])
            ->getMock();

        $this->dockerComposeCli = $this->getMockBuilder(DockerComposeCli::class)
            ->setMethods(['destroy'])
            ->setConstructorArgs([$this->configDir])
            ->getMock();
    }

    /**
     * @covers       DestroyTopologyActions::runTopology()
     * @dataProvider runTopology
     *
     * @param string $id
     * @param string $name
     * @param bool   $result
     */
    public function testDeleteTopology(string $id, string $name, bool $result): void
    {
        $topology = new Topology();
        $topology->setName($name);
        $this->setProperty($topology, 'id', $id);

        $this->dockerComposeCli->method('destroy')->willReturn($result);

        $this->actions->method('getDockerComposeCli')
            ->with($this->configDir . '/' . $id . '-' . $name)
            ->willReturn($this->dockerComposeCli);

        $this->assertEquals($result, $this->actions->deleteTopologyDir($topology, $this->configDir));
    }

    /**
     * @covers       DestroyTopologyActions::deleteQueues()
     * @dataProvider deleteQueues
     *
     * @param string $id
     * @param string $name
     * @param array  $queues
     * @param string $exchange
     */
    public function testDeleteQueues(string $id, string $name, array $queues, string $exchange): void
    {
        $topology = new Topology();
        $topology->setName($name);
        $this->setProperty($topology, 'id', $id);

        $node1 = new Node();
        $this->setProperty($node1, 'id', 'node1');
        $node1->setType(TypeEnum::CUSTOM)->setName('1')->setTopology($topology->getName());

        $node2 = new Node();
        $this->setProperty($node2, 'id', 'node2');
        $node2->setType(TypeEnum::MAPPER)->setName('2')->setTopology($topology->getName());

        $node1->addNext(EmbedNode::from($node2));

        $nodes[] = $node1;
        $nodes[] = $node2;

        $this->rabbitMqHandler->method('deleteQueues')->with($queues)->willReturn(TRUE);
        $this->rabbitMqHandler->method('deleteExchange')->with($exchange)->willReturn(TRUE);

        $this->actions->deleteQueues($topology, $nodes);
    }

    /**
     * @return array
     */
    public function deleteQueues(): array
    {
        return [
            [
                '111222333',
                'too',
                ['pipes.111222333-too.counter', 'pipes.111222333-too.node1-1', 'pipes.111222333-too.node2-2'],
                'pipes.111222333-too.events',
            ],
        ];
    }

    /**
     * @return array
     */
    public function runTopology(): array
    {
        return [
            ['123456789', 'test2', TRUE],
            ['123456789', 'test2', FALSE],
        ];
    }

}
