<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\PublishGuard;

use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Database\Document\Topology;

/**
 * Hook executed by {@see \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::publishTopology}
 * BEFORE the topology-generator is asked to build a bridge.
 *
 * The community implementation is a no-op. Enterprise/cloud builds replace it
 * with a service that enforces plan-level limits (slot count, etc.) so the
 * caller fails fast with a clean exception instead of leaving a half-built
 * bridge behind.
 */
interface PublishGuardInterface
{

    /**
     * @throws TopologyException when the publish must be rejected.
     */
    public function ensureCanPublish(Topology $topology): void;

}
