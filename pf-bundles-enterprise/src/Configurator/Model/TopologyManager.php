<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Configurator\Model;

use Hanaboso\PipesFramework\Configurator\Model\TopologyManager as BaseTopologyManager;
use Hanaboso\PipesFramework\Database\Document\Topology as BaseTopology;
use Hanaboso\PipesFrameworkEnterprise\Database\Document\Topology;

/**
 * Class TopologyManager
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Configurator\Model
 */
final class TopologyManager extends BaseTopologyManager
{

    /**
     * @param BaseTopology $topology
     * @param mixed[]      $data
     *
     * @return BaseTopology
     */
    protected function setTopologyData(BaseTopology $topology, array $data): BaseTopology
    {
        $topology = parent::setTopologyData($topology, $data);

        if ($topology instanceof Topology && isset($data['mcp_description'])) {
            $topology->setMcpDescription($data['mcp_description']);
        }

        return $topology;
    }

    /**
     * @param BaseTopology $topology
     * @param string       $hash
     *
     * @return BaseTopology
     */
    protected function cloneTopologyShallow(BaseTopology $topology, string $hash): BaseTopology
    {
        $clonedTopology = parent::cloneTopologyShallow($topology, $hash);

        if ($clonedTopology instanceof Topology && $topology instanceof Topology) {
            $clonedTopology->setMcpDescription($topology->getMcpDescription());
        }

        return $clonedTopology;
    }

}
