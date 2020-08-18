<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Utils;

use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;

/**
 * Class CronUtils
 *
 * @package Hanaboso\PipesFramework\Configurator\Utils
 */
final class CronUtils
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
            '/topologies/%s/nodes/%s/run',
            $topology->getId(),
            $node->getId()
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
            '%s/%s',
            $topology->getId(),
            $node->getId()
        );
    }

}
