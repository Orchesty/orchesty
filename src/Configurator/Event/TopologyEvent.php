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
     * @var string
     */
    private string $topologyName;

    /**
     * TopologyEvent constructor.
     *
     * @param string $topologyName
     */
    public function __construct(string $topologyName)
    {
        $this->topologyName = $topologyName;
    }

    /**
     * @return mixed
     */
    public function getTopologyName()
    {
        return $this->topologyName;
    }

}
