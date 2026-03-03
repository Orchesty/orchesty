<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Configurator\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Database\Document\Topology as BaseTopology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler as BaseTopologyHandler;
use Hanaboso\PipesFrameworkEnterprise\Database\Document\Topology;

/**
 * Class TopologyHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Configurator\Handler
 */
final class TopologyHandler extends BaseTopologyHandler
{

    /**
     * @param BaseTopology $topology
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    protected function getTopologyData(BaseTopology $topology): array
    {
        $data = parent::getTopologyData($topology);

        if ($topology instanceof Topology) {
            $data['mcp_description'] = $topology->getMcpDescription();
        }

        return $data;
    }

}
