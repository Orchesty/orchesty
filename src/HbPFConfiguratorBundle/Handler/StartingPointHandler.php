<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:07 PM
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Event\TopologyEvent;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\TopologyGenerator\Request\RequestHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StartingPointHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
class StartingPointHandler
{

    /**
     * @var NodeRepository|ObjectRepository
     */
    private $nodeRepository;

    /**
     * @var TopologyRepository|ObjectRepository
     */
    private $topologyRepository;

    /**
     * @var StartingPoint
     */
    private $startingPoint;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var RequestHandler
     */
    private $requestHandler;

    /**
     * StartingPointHandler constructor.
     *
     * @param DocumentManager                          $dm
     * @param StartingPoint                            $startingPoint
     * @param EventDispatcher|EventDispatcherInterface $dispatcher
     * @param RequestHandler                           $requestHandler
     */
    public function __construct(DocumentManager $dm, StartingPoint $startingPoint, EventDispatcherInterface $dispatcher,
                                RequestHandler $requestHandler)
    {
        $this->startingPoint      = $startingPoint;
        $this->nodeRepository     = $dm->getRepository(Node::class);
        $this->topologyRepository = $dm->getRepository(Topology::class);
        $this->dispatcher         = $dispatcher;
        $this->requestHandler     = $requestHandler;
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
            $this->dispatcher->dispatch(TopologyEvent::EVENT, new TopologyEvent($topologyName));
            throw new Exception(sprintf('The topology[name=%s] does not exist.', $topologyName));
        }

        return $topologies;
    }

    /**
     * @param string   $nodeName
     * @param Topology $topology
     *
     * @return Node
     * @throws Exception
     */
    public function getNodeByName(string $nodeName, Topology $topology): Node
    {
        $node = $this->nodeRepository->getNodeByTopology($nodeName, $topology->getId());

        if (empty($node)) {
            $this->dispatcher->dispatch(TopologyEvent::EVENT, new TopologyEvent($topology->getName()));
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
                $this->getNodeByName($nodeName, $topology));
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
     * @param string|null $body
     */
    public function run(string $topologyName, string $nodeName, ?string $body = NULL): void
    {
        $topologies = $this->getTopologies($topologyName);
        foreach ($topologies as $topology) {
            $this->startingPoint->run($topology, $this->getNodeByName($nodeName, $topology), $body);
        }
    }

    /**
     * @param string $topologyId
     *
     * @return array
     */
    public function runTest(string $topologyId): array
    {
        $startTopology = TRUE;
        $runningInfo = $this->requestHandler->infoTopology($topologyId);
        if ($runningInfo instanceof ResponseDto && $runningInfo->getBody()) {
            $result = json_decode($runningInfo->getBody(), TRUE);
            if (array_key_exists('docker_info', $result) && count($result['docker_info'])) {
                $startTopology = FALSE;
            }
        }

        if ($startTopology) {
            $this->requestHandler->generateTopology($topologyId);
            $this->requestHandler->runTopology($topologyId);
        }

        return $this->startingPoint->runTest($this->getTopology($topologyId));
    }

}
