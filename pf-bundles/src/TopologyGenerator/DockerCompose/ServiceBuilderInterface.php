<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 3:12 PM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\Configurator\Document\Node;

/**
 * Interface ServiceBuilderInterface
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
interface ServiceBuilderInterface
{

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service;

}