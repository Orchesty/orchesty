<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\PublishGuard;

use Hanaboso\PipesFramework\Database\Document\Topology;

/**
 * Class NullPublishGuard
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\PublishGuard
 *
 * Default community-edition guard - permits every publish.
 */
final class NullPublishGuard implements PublishGuardInterface
{

    /**
     * @param Topology $topology
     */
    public function ensureCanPublish(Topology $topology): void
    {
        unset($topology);
    }

}
