<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Hanaboso\PipesFramework\Configurator\Model\ProgressManager;

/**
 * Class TopologyProgressHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class TopologyProgressHandler
{

    /**
     * @var ProgressManager
     */
    private ProgressManager $manager;

    /**
     * TopologyProgressHandler constructor.
     *
     * @param ProgressManager $manager
     */
    public function __construct(ProgressManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param string $topologyId
     *
     * @return array<mixed>
     */
    public function getProgress(string $topologyId): array
    {
        return ['items' => $this->manager->getProgress($topologyId)];
    }

}
