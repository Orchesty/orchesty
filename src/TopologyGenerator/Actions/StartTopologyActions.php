<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 11.10.17
 * Time: 17:35
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\Actions;

use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\DockerComposeCli;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Generator;

/**
 * Class StartTopologyActions
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\Actions
 */
class StartTopologyActions extends ActionsAbstract
{

    /**
     * @param Topology $topology
     * @param string   $dstDirectory
     *
     * @return bool
     */
    public function runTopology(Topology $topology, string $dstDirectory): bool
    {
        $dstTopologyDirectory = Generator::getTopologyDir($topology, $dstDirectory);
        $cli                  = $this->getDockerComposeCli($dstTopologyDirectory);

        return $cli->up();
    }

}
