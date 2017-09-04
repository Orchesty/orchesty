<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\Commons\Topology\TopologyRepository;

/**
 * Class TopologyHandler
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler
 */
class TopologyHandler
{

    /**
     * @var DocumentRepository|TopologyRepository
     */
    private $topologyRepository;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * TopologyHandler constructor.
     *
     * @param DatabaseManagerLocator $dml
     */
    public function __construct(DatabaseManagerLocator $dml)
    {
        $this->dm                 = $dml->getDm();
        $this->topologyRepository = $this->dm->getRepository(Topology::class);
    }

    /**
     * @param null $limit
     * @param null $offset
     * @param null $orderBy
     *
     * @return array
     */
    public function getTopologies($limit = NULL, $offset = NULL, $orderBy = NULL): array
    {
        $sort = [];
        if (!empty($orderBy)) {
            foreach (explode(',', $orderBy) as $item) {
                $name        = substr($item, 0, -1);
                $direction   = substr($item, -1);
                $sort[$name] = $direction;
            }
        }

        $topologies = $this->topologyRepository->findBy([], $sort, $limit, $offset);

        $data = [];
        foreach ($topologies as $topology) {
            $data['items'][] = $this->getTopologyData($topology);
        }

        $data['total']  = $this->topologyRepository->getTotalCount();
        $data['limit']  = $limit;
        $data['count']  = count($data['items']);
        $data['offset'] = $offset;

        return $data;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function getTopology(string $id): array
    {
        /** @var Topology $topology */
        $topology = $this->topologyRepository->find($id);

        $data = [];
        if ($topology) {
            $data = $this->getTopologyData($topology);
        }

        return $data;
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     */
    public function updateTopology(string $id, array $data): array
    {
        /** @var Topology $topology */
        $topology = $this->topologyRepository->find($id);

        // TODO create method for getting topology by ID, throw exception if not found

        $topology->setName($data['name']);
        $topology->setDescr($data['descr']);
        $topology->setEnabled($data['enabled']);
        $this->dm->flush();

        return $this->getTopologyData($topology);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public function getTopologySchema(string $id): string
    {
        /** @var Topology $topology */
        $topology = $this->topologyRepository->find($id);

        return $topology->getBpmn();
    }

    /**
     * @param string $id
     * @param array  $data
     */
    public function saveTopologySchema(string $id, array $data): void
    {
        // TODO
    }

    /**
     * @param Topology $topology
     *
     * @return array
     */
    private function getTopologyData(Topology $topology): array
    {
        return [
            '_id'     => $topology->getId(),
            'name'    => $topology->getName(),
            'descr'   => $topology->getDescr(),
            'enabled' => $topology->isEnabled(),
        ];
    }

}
