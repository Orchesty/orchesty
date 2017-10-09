<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:07 PM
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StartingPointHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
class StartingPointHandler
{

    /**
     * @var NodeRepository|DocumentRepository
     */
    private $nodeRepository;

    /**
     * @var TopologyRepository|DocumentRepository
     */
    private $topologyRepository;

    /**
     * @var StartingPoint
     */
    private $startingPoint;

    /**
     * StartingPointHandler constructor.
     *
     * @param DocumentManager $dm
     * @param StartingPoint   $startingPoint
     */
    public function __construct(DocumentManager $dm, StartingPoint $startingPoint)
    {
        $this->startingPoint      = $startingPoint;
        $this->nodeRepository     = $dm->getRepository(Node::class);
        $this->topologyRepository = $dm->getRepository(Topology::class);
    }

    /**
     * @param string $topologyName
     *
     * @return Topology[]
     * @throws Exception
     */
    public function getTopologies(string $topologyName): array
    {
        $topologies = $this->topologyRepository->getRunnableTopologies($topologyName);

        if (empty($topologies)) {
            throw new Exception(sprintf('The topology[name=%s] does not exist.', $topologyName));
        }

        return $topologies;
    }

    /**
     * @param string $nodeName
     * @param string $topologyId
     *
     * @return Node
     * @throws Exception
     */
    public function getNodeByName(string $nodeName, string $topologyId): Node
    {
        $node = $this->nodeRepository->getNodeByTopology($nodeName, $topologyId);

        if (empty($node)) {
            throw new Exception(sprintf('The node[name=%s] does not exist.', $nodeName));
        }

        return $node;
    }

    /**
     * @param string $topologyId
     *
     * @return Topology
     * @throws Exception
     */
    public function getTopology(string $topologyId): Topology
    {
        $topology = $this->topologyRepository->find($topologyId);

        if (!$topology) {
            throw new Exception(sprintf('The topology[id=%s] does not exist.', $topologyId));
        }

        return $topology;
    }

    /**
     * @param string $nodeId
     *
     * @return Node
     * @throws Exception
     */
    public function getNode(string $nodeId): Node
    {
        $node = $this->nodeRepository->find($nodeId);

        if (!$node) {
            throw new Exception(sprintf('The node[id=%s] does not exist.', $nodeId));
        }

        return $node;
    }

    /**
     * @param Request $request
     * @param string  $topologyName
     * @param string  $nodeName
     */
    public function runWithRequest(Request $request, string $topologyName, string $nodeName): void
    {
        $topologies = $this->getTopologies($topologyName);
        foreach ($topologies as $topology) {
            $this->startingPoint->runWithRequest($request, $topology,
                $this->getNodeByName($nodeName, $topology->getId()));
        }
    }

    /**
     * @param Request $request
     * @param string  $topologyId
     * @param string  $nodeId
     */
    public function runWithRequestById(Request $request, string $topologyId, string $nodeId): void
    {
        $this->startingPoint->runWithRequest($request, $this->getTopology($topologyId), $this->getNode($nodeId));
    }

    /**
     * @param string      $topologyName
     * @param string      $nodeName
     * @param string|null $body         JSON string
     */
    public function run(string $topologyName, string $nodeName, ?string $body = NULL): void
    {
        $topologies = $this->getTopologies($topologyName);
        foreach ($topologies as $topology) {
            $this->startingPoint->run($topology, $this->getNodeByName($nodeName, $topology->getId()), $body);
        }
    }

    /**
     * @param string $topologyId
     *
     * @return array
     */
    public function runTest(string $topologyId): array
    {
        return $this->startingPoint->runTest($this->getTopology($topologyId));
    }

}