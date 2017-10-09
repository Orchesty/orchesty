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
    public function getNode(string $nodeName, string $topologyId): Node
    {
        $node = $this->nodeRepository->getNodeByTopology($nodeName, $topologyId);

        if (empty($node)) {
            throw new Exception(sprintf('The node[name=%s] does not exist.', $nodeName));
        }

        return $node;
    }

    /**
     * @param string $id
     *
     * @return Topology
     * @throws Exception
     */
    protected function getTopologyById(string $id): Topology
    {
        $topology = $this->topologyRepository->findOneBy(['id' => $id]);

        if (!$topology) {
            throw new Exception(sprintf('The topology[id=%s] does not exist.', $id));
        }

        return $topology;
    }

    /**
     * @param Request $request
     * @param string  $topologyName
     * @param string  $nodeName
     */
    public function runWithRequest(Request $request, string $topologyName, string $nodeName): void
    {
        $tops = $this->getTopologies($topologyName);
        foreach ($tops as $top) {
            $this->startingPoint->runWithRequest($request, $top, $this->getNode($nodeName, $top->getId()));
        }
    }

    /**
     * @param string      $topologyName
     * @param string      $nodeName
     * @param string|null $body JSON string
     */
    public function run(string $topologyName, string $nodeName, ?string $body = NULL): void
    {
        $tops = $this->getTopologies($topologyName);
        foreach ($tops as $top) {
            $this->startingPoint->run($top, $this->getNode($nodeName, $top->getId()), $body);
        }
    }

    /**
     * @param string $topologyId
     *
     * @return array
     */
    public function runTest(string $topologyId): array
    {
        return $this->startingPoint->runTest($this->getTopologyById($topologyId));
    }

}