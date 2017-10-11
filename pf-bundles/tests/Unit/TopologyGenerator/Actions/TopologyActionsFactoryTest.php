<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 12.10.17
 * Time: 15:07
 */

namespace Tests\Unit\TopologyGenerator\Actions;

use Hanaboso\PipesFramework\Commons\Docker\Handler\DockerHandler;
use Hanaboso\PipesFramework\RabbitMq\Handler\RabbitMqHandler;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\DestroyTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\GenerateTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\StartTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\StopTopologyActions;
use Hanaboso\PipesFramework\TopologyGenerator\Actions\TopologyActionsFactory;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinitionFactory;
use Hanaboso\PipesFramework\TopologyGenerator\Exception\TopologyGeneratorException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\PrivateTrait;

/**
 * Class TopologyActionsFactoryTest
 *
 * @package Tests\Unit\TopologyGenerator\Actions
 */
class TopologyActionsFactoryTest extends TestCase
{

    use PrivateTrait;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|DockerHandler
     */
    protected $dockerHandler;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|RabbitMqHandler
     */
    protected $rabbitMqHandler;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|VolumePathDefinitionFactory
     */
    protected $volumePath;

    /**
     * setUp
     */
    public function setUp(): void
    {
        $this->dockerHandler   = $this
            ->getMockBuilder(DockerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rabbitMqHandler = $this
            ->getMockBuilder(RabbitMqHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->volumePath      = $this
            ->getMockBuilder(VolumePathDefinitionFactory::class)
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @covers       TopologyActionsFactory::getTopologyAction()
     * @dataProvider getTopology
     *
     * @param string      $type
     * @param string      $typeOf
     * @param null|string $property
     * @param null|string $exception
     */
    public function testGetTopologyAction(string $type, string $typeOf, ?string $property, ?string $exception): void
    {
        if ($exception) {
            $this->expectException($exception);
        }

        $action = new TopologyActionsFactory($this->dockerHandler, $this->rabbitMqHandler, $this->volumePath);

        if ($property) {
            $this->assertNull($this->getProperty($action, $property));
        }

        $this->assertInstanceOf($typeOf, $action->getTopologyAction($type));

        if ($property) {
            $this->assertInstanceOf($typeOf, $this->getProperty($action, $property));
        }
    }

    /**
     * @return array
     */
    public function getTopology(): array
    {
        return [
            [TopologyActionsFactory::START, StartTopologyActions::class, 'startAction', NULL],
            [TopologyActionsFactory::STOP, StopTopologyActions::class, 'stopAction', NULL],
            [TopologyActionsFactory::DESTROY, DestroyTopologyActions::class, 'destroyAction', NULL],
            [TopologyActionsFactory::GENERATE, GenerateTopologyActions::class, 'generateAction', NULL],
            ['foo', GenerateTopologyActions::class, NULL, TopologyGeneratorException::class],
        ];
    }

}
