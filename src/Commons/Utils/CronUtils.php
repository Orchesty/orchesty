<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Utils;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;

/**
 * Class CronUtils
 *
 * @package Hanaboso\PipesFramework\Commons\Utils
 */
class CronUtils
{

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @return string
     */
    public static function getTopologyUrl(Topology $topology, Node $node): string
    {
        return sprintf(
            '/api/topologies/%s/nodes/%s/run',
            $topology->getName(),
            $node->getName()
        );
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @return string
     */
    public static function getHash(Topology $topology, Node $node): string
    {
        return sprintf(
            '%s-%s-%s',
            $topology->getName(),
            $topology->getVersion(),
            $node->getName()
        );
    }

}