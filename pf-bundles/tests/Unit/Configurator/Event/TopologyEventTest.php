<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Configurator\Event;

use Hanaboso\PipesFramework\Configurator\Event\TopologyEvent;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class TopologyEventTest
 *
 * @package PipesFrameworkTests\Unit\Configurator\Event
 *
 * @covers  \Hanaboso\PipesFramework\Configurator\Event\TopologyEvent
 */
final class TopologyEventTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Event\TopologyEvent::getTopologyName
     */
    public function testEvent(): void
    {
        self::assertEquals('Name', (new TopologyEvent('Name'))->getTopologyName());
    }

}
