<?php declare(strict_types=1);

    /**
     * Created by PhpStorm.
     * User: radek.jirsa
     * Date: 30.11.17
     * Time: 11:31
     */

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Handler;

/**
 * Class MetricsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFMetricsBundle\Handler
 */
class MetricsHandler
{

    /**
     * @param string $topologyName
     * @param array  $params
     *
     * @return array
     */
    public function getTopologyMetrics(string $topologyName, array $params): array
    {
        return [];
    }

    /**
     * @param string $topologyName
     * @param string $nodeName
     * @param array  $params
     *
     * @return array
     */
    public function getNodeMetrics(string $topologyName, string $nodeName, array $params): array
    {
        return [];
    }

}