<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class TopologyEvent
 *
 * @package Hanaboso\PipesFramework\Configurator\Event
 */
final class TopologyEvent extends Event
{

    public const EVENT = 'topology_event';

    /**
     * TopologyEvent constructor.
     *
     * @param string $topologyName
     */
    public function __construct(private string $topologyName)
    {
    }

    /**
     * @return string
     */
    public function getTopologyName(): string
    {
        return $this->topologyName;
    }

}
