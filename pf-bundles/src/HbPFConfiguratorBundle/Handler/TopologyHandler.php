<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Utils\UriParams;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;

/**
 * Class TopologyHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
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

        $data = [
            'items' => [],
        ];
        foreach ($topologies as $topology) {
            $data['items'][] = $this->getTopologyData($topology);
        }

        $data['total']  = $this->topologyRepository->getTotalCount();
        $data['limit']  = $limit;
        $data['count']  = count($topologies);
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
     * @param string $id
     *
     * @return bool
     */
    public function deleteTopology(string $id): bool
    {
        $topology = $this->getTopologyById($id);
        $this->manager->deleteTopology($topology);

        return TRUE;
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
            'status'     => $topology->getStatus(),
            'visibility' => $topology->getVisibility(),
            'version'    => $topology->getVersion(),
            'enabled'    => $topology->isEnabled(),
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
