<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 30.11.17
 * Time: 10:31
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Directives\Configs;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Service;

/**
 * Trait ServiceTrait
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
trait ServiceTrait
{

    /**
     * @param Service $service
     * @param string  $topologyMode
     * @param string  $topologyPrefix
     * @param string  $volumePath
     */
    protected function addServiceEnvironment(Service $service, string $topologyMode, string $topologyPrefix,
                                             string $volumePath)
    {
        switch ($topologyMode) {
            case GeneratorHandler::MODE_SWARM:
                $service->addConfigs(new Configs($topologyPrefix, '/srv/app/topology/topology.json'));
                break;
            case GeneratorHandler::MODE_COMPOSE:
                $service->addVolume($volumePath . ':/srv/app/topology/topology.json');
                break;
        }
    }

}
