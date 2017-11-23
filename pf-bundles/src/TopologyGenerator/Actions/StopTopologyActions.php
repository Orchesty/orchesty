<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 11.10.17
 * Time: 19:49
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\Actions;

use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Generator;

/**
 * Class StopTopologyActions
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\Actions
 */
class StopTopologyActions extends ActionsAbstract
{

    /**
     * @param Topology $topology
     * @param string   $dstDirectory
     * @param string   $topologyprefix
     *
     * @return bool
     */
    public function stopTopology(Topology $topology, string $dstDirectory, string $topologyprefix): bool
    {
        $dstTopologyDirectory = Generator::getTopologyDir($topology, $dstDirectory);
        $cli                  = $this->getDockerComposeCli($dstTopologyDirectory, $topologyprefix);

        return $cli->stop($this->mode);
    }

}
