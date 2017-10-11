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
use Hanaboso\PipesFramework\TopologyGenerator\Actions\GenerateTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Generator;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinitionFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\PrivateTrait;

/**
 * Class GenerateTopologyActionsTest
 *
 * @package Tests\Unit\TopologyGenerator\Actions
 */
class GenerateTopologyActionsTest extends TestCase
{

    use PrivateTrait;

    /**
     * @var DockerHandler|PHPUnit_Framework_MockObject_MockObject
     */
    protected $dockerHandler;

    /**
     * @var VolumePathDefinitionFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $volumePathDefinitionFactory;

    /**
     * @var GenerateTopologyActions|PHPUnit_Framework_MockObject_MockObject
     */
    protected $actions;

    /**
     * @var string
     */
    protected $configDir;

    /**
     *
     */
    public function setUp()
    {
        $this->configDir = '/opt/srv/topology';

        $this->dockerHandler = $this->getMockBuilder(DockerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->volumePathDefinitionFactory = $this->getMockBuilder(VolumePathDefinitionFactory::class)
            ->setConstructorArgs(['/home/web/foo'])
            ->getMock();

        $this->actions = $this
            ->getMockBuilder(GenerateTopologyActions::class)
            ->setConstructorArgs([$this->dockerHandler, $this->volumePathDefinitionFactory])
            ->setMethods(['getGenerator'])
            ->getMock();
    }

    /**
     * @covers       GenerateTopologyActions::runTopology()
     * @dataProvider generateTopology
     *
     * @param Topology $topology
     * @param array    $nodes
     * @param string   $network
     * @param bool     $result
     */
    public function testGenerateTopology(Topology $topology, array $nodes, string $network, bool $result): void
    {
        $generator = $this->getMockBuilder(Generator::class)->disableOriginalConstructor()->getMock();
        $generator->method('generate')->willReturn(NULL);

        $this->actions->method('getGenerator')
            ->with($this->configDir, $network, $this->volumePathDefinitionFactory)
            ->willReturn($generator);

        $this->assertEquals($result, $this->actions->generateTopology($topology, $nodes, $this->configDir, $network));
    }

    /**
     * @return array
     */
    public function generateTopology(): array
    {
        $topology = new Topology();
        $topology->setName('test1');
        $this->setProperty($topology, 'id', '123456789');

        $node1 = new Node();
        $this->setProperty($node1, 'id', 'node1');
        $node1->setType(TypeEnum::CUSTOM)->setName('1')->setTopology($topology->getName());

        $node2 = new Node();
        $this->setProperty($node2, 'id', 'node2');
        $node2->setType(TypeEnum::MAPPER)->setName('2')->setTopology($topology->getName());

        $node1->addNext(EmbedNode::from($node2));

        $nodes[] = $node1;
        $nodes[] = $node2;

        return [
            [
                $topology,
                $nodes,
                'demo',
                TRUE,
            ],
        ];
    }
}
