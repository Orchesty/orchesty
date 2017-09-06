<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\ApiGateway\Manager\TopologyManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Exception\TopologyException;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\Commons\Topology\TopologyRepository;
use Hanaboso\PipesFramework\Commons\Utils\UriParams;

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
     * @var TopologyManager
     */
    private $manager;

    /**
     * TopologyHandler constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param TopologyManager        $manager
     */
    public function __construct(DatabaseManagerLocator $dml, TopologyManager $manager)
    {
        $this->dm                 = $dml->getDm();
        $this->topologyRepository = $this->dm->getRepository(Topology::class);
        $this->manager            = $manager;
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
        $sort       = UriParams::parseOrderBy($orderBy);
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
        $topology = $this->getTopologyById($id);

        return $this->getTopologyData($topology);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function createTopology(array $data): array
    {
        $topology = $this->manager->createTopology($data);

        return $this->getTopologyData($topology);

    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     */
    public function updateTopology(string $id, array $data): array
    {
        $topology = $this->getTopologyById($id);
        $this->manager->updateTopology($topology, $data);

        return $this->getTopologyData($topology);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public function getTopologySchema(string $id): string
    {
        return $this->getTopologyById($id)->getRawBpmn();
    }

    /**
     * @param string $id
     * @param string $content
     * @param array  $data
     *
     * @return string[]
     */
    public function saveTopologySchema(string $id, string $content, array $data): array
    {
        $topology = $this->getTopologyById($id);
        $topology = $this->manager->saveTopologySchema($topology, $content, $data);

        return $this->getTopologyData($topology);
    }

    /**
     * @param string $id
     *
     * @return string[]
     */
    public function publishTopology(string $id): array
    {
        $topology = $this->getTopologyById($id);
        $this->manager->publishTopology($topology);

        return $this->getTopologyData($topology);
    }

    /**
     * @param string $id
     *
     * @return string[]
     */
    public function cloneTopology(string $id): array
    {
        $topology = $this->getTopologyById($id);
        $this->manager->cloneTopology($topology);

        return $this->getTopologyData($topology);
    }

    /**
     * @param Topology $topology
     *
     * @return array
     */
    private function getTopologyData(Topology $topology): array
    {
        return [
            '_id'        => $topology->getId(),
            'name'       => $topology->getName(),
            'descr'      => $topology->getDescr(),
            'enabled'    => $topology->isEnabled(),
            'visibility' => $topology->getVisibility(),
            'status'     => $topology->getStatus(),
        ];
    }

    /**
     * @param string $id
     *
     * @return Topology
     * @throws TopologyException
     */
    private function getTopologyById(string $id): Topology
    {
        $res = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $id]);

        if (!$res) {
            throw new TopologyException(
                sprintf('Topology with [%s] id was not found.', $id),
                TopologyException::TOPOLOGY_NOT_FOUND
            );
        }

        return $res;
    }

}
