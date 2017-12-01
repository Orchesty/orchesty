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
use Hanaboso\PipesFramework\TopologyGenerator\Actions\StopTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\DockerComposeCli;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\PrivateTrait;

/**
 * Class StopTopologyActionsTest
 *
 * @package Tests\Unit\TopologyGenerator\Actions
 */
class StopTopologyActionsTest extends TestCase
{

    use PrivateTrait;

    /**
     * @var DockerHandler|MockObject
     */
    protected $dockerHandler;

    /**
     * @var StopTopologyActions|MockObject
     */
    protected $actions;

    /**
     * @var DockerComposeCli|MockObject
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

        $this->dockerHandler = $this->getMockBuilder(DockerHandler::class)->disableOriginalConstructor()->getMock();

        $this->actions = $this->getMockBuilder(StopTopologyActions::class)
            ->setConstructorArgs([$this->dockerHandler])
            ->setMethods(['getDockerComposeCli'])
            ->getMock();

        $this->dockerComposeCli = $this->getMockBuilder(DockerComposeCli::class)
            ->setMethods(['stop'])
            ->setConstructorArgs([$this->configDir])
            ->getMock();
    }

    /**
     * @covers       StoptTopologyActions::runTopology()
     * @dataProvider runTopology
     *
     * @param string $id
     * @param string $name
     * @param bool   $result
     */
    public function testRunTopology(string $id, string $name, bool $result): void
    {
        $topology = new Topology();
        $topology->setName($name);
        $this->setProperty($topology, 'id', $id);

        $this->dockerComposeCli->method('stop')->willReturn($result);

        $this->actions->method('getDockerComposeCli')
            ->with($this->configDir . '/' . $id . '-' . $name)
            ->willReturn($this->dockerComposeCli);

        $this->assertEquals($result, $this->actions->stopTopology($topology, $this->configDir));
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
