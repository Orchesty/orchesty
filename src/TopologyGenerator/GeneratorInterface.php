<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 3:07 PM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;

/**
 * Interface GeneratorInterface
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator
 */
interface GeneratorInterface
{

    /**
     * @param Topology        $topology
     * @param iterable|Node[] $nodes
     */
    public function generate(Topology $topology, iterable $nodes): void;

}