<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Service;

/**
 * Class MultiNodeServiceBuilder
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
class MultiNodeServiceBuilder extends NodeServiceBuilder
{

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service
    {
        $service = parent::build($node);
        $service->setCommand('./dist/src/bin/pipes.js start all_nodes');

        return $service;
    }

}