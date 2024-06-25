<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Event;

use Hanaboso\PipesFramework\Configurator\Event\TopologyEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class TopologyEventTest
 *
 * @package PipesFrameworkTests\Unit\Configurator\Event
 *
 * @covers  \Hanaboso\PipesFramework\Configurator\Event\TopologyEvent
 */
#[CoversClass(TopologyEvent::class)]
final class TopologyEventTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testEvent(): void
    {
        self::assertEquals('Name', (new TopologyEvent('Name'))->getTopologyName());
    }

}
