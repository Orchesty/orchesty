<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler;

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
     * TopologyHandler constructor.
     *
     * @param DatabaseManagerLocator $dml
     */
    public function __construct(DatabaseManagerLocator $dml)
    {
        $dm                       = $dml->getDm();
        $this->topologyRepository = $dm->getRepository(Topology::class);
    }

    /**
     * @param null $limit
     * @param null $offset
     * @param null $orderBy
     */
    public function getTopologies($limit = NULL, $offset = NULL, $orderBy = NULL): void
    {
        $sort = '';

        $topologies = $this->topologyRepository->findBy([], $sort, $limit, $offset);

        $data           = [];
        $data['total']  = '';
        $data['limit']  = '';
        $data['count']  = '';
        $data['offset'] = '';

        foreach ($topologies as $topology) {
            // TODO $data['items'][] = '';
        }

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
            $data['_id']    = $topology->getId();
            $data['name']   = $topology->getName();
            $data['descr']  = $topology->getDescr();
            $data['status'] = $topology->getStatus();
            $data['nodes']  = $topology->getNodes();
        }

        return $data;
    }

    /**
     * @param string $id
     * @param array  $data
     */
    public function updateTopology(string $id, array $data): void
    {

    }

    /**
     * @param string $id
     */
    public function getTopologyScheme(string $id): void
    {

    }

    /**
     * @param string $id
     * @param array  $data
     */
    public function uploadTopologyScheme(string $id, array $data): void
    {

    }

}
