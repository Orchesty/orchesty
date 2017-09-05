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
     * @param Topology $topology
     * @param array    $data
     *
     * @return Topology
     */
    public function updateTopology(Topology $topology, array $data): Topology
    {
        $topology->setName($data['name']);
        $topology->setDescr($data['descr']);
        $topology->setEnabled($data['enabled']);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology $topology
     * @param array    $data
     *
     * @return Topology
     */
    public function saveTopologySchema(Topology $topology, array $data): Topology
    {
        if ($topology->getStatus() === TopologyStatusEnum::PUBLIC) {
            $res = $this->cloneTopology($topology, $data['name']);
        } else {
            $res = $topology;
            $res->setName($data['name']);
            $this->dm->persist($res);
        }

        $res->setDescr($data['descr']);
        $res->setEnabled($data['enabled']);

        return $res;
    }

    /**
     * @param Topology $topology
     *
     * @return Topology
     */
    public function publishTopology(Topology $topology): Topology
    {
        $topology->setStatus(TopologyStatusEnum::PUBLIC);
        $this->dm->flush();

        return $topology;
    }

    /**
     * @param Topology    $topology
     * @param string|NULL $name
     *
     * @return Topology
     */
    public function cloneTopology(Topology $topology, ?string $name = NULL): Topology
    {
        $res = new Topology();
        $res
            ->setStatus(TopologyStatusEnum::DRAFT)
            ->setName(
                $name ?? $topology->getName() . ' - copy'
            )
            ->setDescr($topology->getDescr())
            ->setEnabled($topology->isEnabled())
            ->setBpmn($topology->getBpmn());
        $this->dm->persist($res);

        return $res;
    }

}