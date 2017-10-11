<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 12.10.17
 * Time: 11:24
 */

namespace Tests\Unit\TopologyGenerator\Actions;

use Hanaboso\PipesFramework\Commons\Docker\Handler\DockerHandler;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\StartTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\DockerComposeCli;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\PrivateTrait;

/**
 * Class StartTopologyActionsTest
 *
 * @package Tests\Unit\TopologyGenerator\Actions
 */
class StartTopologyActionsTest extends TestCase
{

    use PrivateTrait;

    /**
     * @var DockerHandler|PHPUnit_Framework_MockObject_MockObject
     */
    protected $dockerHandler;

    /**
     * @var StartTopologyActions|PHPUnit_Framework_MockObject_MockObject
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
     *
     */
    public function setUp()
    {
        $this->configDir = '/opt/srv/topology';

        $this->dockerHandler = $this->getMockBuilder(DockerHandler::class)->disableOriginalConstructor()->getMock();

        $this->actions = $this->getMockBuilder(StartTopologyActions::class)
            ->setConstructorArgs([$this->dockerHandler])
            ->setMethods(['getDockerComposeCli'])
            ->getMock();

        $this->dockerComposeCli = $this->getMockBuilder(DockerComposeCli::class)
            ->setMethods(['up'])
            ->setConstructorArgs([$this->configDir])
            ->getMock();
    }

    /**
     * @covers StartTopologyActions::runTopology()
     * @dataProvider runTopology
     */
    public function testRunTopology(string $id, string $name, bool $result): void
    {
        $topology = new Topology();
        $topology->setName($name);
        $this->setProperty($topology, 'id', $id);

        $this->dockerComposeCli->method('up')->willReturn($result);

        $this->actions->method('getDockerComposeCli')
            ->with($this->configDir . '/' . $id . '-' . $name)
            ->willReturn($this->dockerComposeCli);

        $this->assertEquals($result, $this->actions->runTopology($topology, $this->configDir));
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
