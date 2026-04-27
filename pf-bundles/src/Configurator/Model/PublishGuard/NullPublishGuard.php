<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\PublishGuard;

use Hanaboso\PipesFramework\Database\Document\Topology;

/**
 * Default community-edition guard - permits every publish.
 */
final class NullPublishGuard implements PublishGuardInterface
{

    public function ensureCanPublish(Topology $topology): void
    {
    }

}
