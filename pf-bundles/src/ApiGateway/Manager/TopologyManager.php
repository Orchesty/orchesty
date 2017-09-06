<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;

/**
 * Class TopologyManager
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Manager
 */
class TopologyManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * TopologyManager constructor.
     *
     * @param DatabaseManagerLocator $dml
     */
    function __construct(DatabaseManagerLocator $dml)
    {
        $this->dm = $dml->getDm();
    }

    /**
     * @param array $data
     *
     * @return Topology
     */
    public function createTopology(array $data): Topology
    {
        $topology = $this->setTopologyData(new Topology(), $data);

        $this->dm->persist($topology);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     * @param array    $data
     *
     * @return Topology
     */
    public function updateTopology(Topology $topology, array $data): Topology
    {
        $topology = $this->setTopologyData($topology, $data);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     * @param string   $content
     * @param array    $data
     *
     * @return Topology
     */
    public function saveTopologySchema(Topology $topology, string $content, array $data): Topology
    {
        if ($topology->getVisibility() === TopologyStatusEnum::PUBLIC) {
            $topology = $this->cloneTopology($topology);
        }

        $topology
            ->setBpmn($data)
            ->setRawBpmn($content);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     *
     * @return Topology
     */
    public function publishTopology(Topology $topology): Topology
    {
        $topology->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     *
     * @return Topology
     */
    public function cloneTopology(Topology $topology): Topology
    {
        $res = new Topology();
        $res
            ->setName($topology->getName() . ' - copy')
            ->setDescr($topology->getDescr())
            ->setEnabled($topology->isEnabled())
            ->setBpmn($topology->getBpmn())
            ->setRawBpmn($topology->getRawBpmn());
        $this->dm->persist($res);

        return $res;
    }

    /**
     * @param Topology $topology
     * @param array    $data
     *
     * @return Topology
     */
    private function setTopologyData(Topology $topology, array $data): Topology
    {
        if (isset($data['name'])) {
            $topology->setName($data['name']);
        }

        if (isset($data['descr'])) {
            $topology->setDescr($data['descr']);
        }

        if (isset($data['enabled'])) {
            $topology->setEnabled($data['enabled']);
        }

        if (isset($data['visibility'])) {
            $topology->setVisibility($data['visibility']);
        }

        if (isset($data['status'])) {
            $topology->setStatus($data['status']);
        }

        return $topology;
    }

}