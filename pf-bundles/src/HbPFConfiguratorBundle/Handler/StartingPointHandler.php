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
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Event\TopologyEvent;
use Hanaboso\PipesFramework\Configurator\Exception\StartingPointException;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
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
    public function __construct(DocumentManager $dm,
                                StartingPoint $startingPoint,
                                EventDispatcherInterface $dispatcher,
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
     * @throws PipesFrameworkException
     * @throws MongoDBException
     */
    public function getTopologies(string $topologyName): array
    {
        $topologies = $this->topologyRepository->getRunnableTopologies($topologyName);

        if (empty($topologies)) {
            $this->dispatcher->dispatch(TopologyEvent::EVENT, new TopologyEvent($topologyName));
            throw new PipesFrameworkException(sprintf('The topology[name=%s] does not exist.', $topologyName));
        }

        return $topologies;
    }

    /**
     * @param string   $nodeName
     * @param Topology $topology
     *
     * @return Node
     * @throws PipesFrameworkException
     */
    public function getNodeByName(string $nodeName, Topology $topology): Node
    {
        $node = $this->nodeRepository->getNodeByTopology($nodeName, $topology->getId());

        if (empty($node)) {
            $this->dispatcher->dispatch(TopologyEvent::EVENT, new TopologyEvent($topology->getName()));
            throw new PipesFrameworkException(sprintf('The node[name=%s] does not exist.', $nodeName));
        }

        return $node;
    }

    /**
     * @param string $topologyId
     *
     * @return Topology
     * @throws PipesFrameworkException
     * @throws LockException
     * @throws MappingException
     */
    public function getTopology(string $topologyId): Topology
    {
        /** @var Topology|null $topology */
        $topology = $this->topologyRepository->find($topologyId);

        if (!$topology) {
            throw new PipesFrameworkException(sprintf('The topology[id=%s] does not exist.', $topologyId));
        }

        return $topology;
    }

    /**
     * @param string $nodeId
     *
     * @return Node
     * @throws LockException
     * @throws MappingException
     * @throws PipesFrameworkException
     */
    public function getNode(string $nodeId): Node
    {
        /** @var Node|null $node */
        $node = $this->nodeRepository->find($nodeId);

        if (!$node) {
            throw new PipesFrameworkException(sprintf('The node[id=%s] does not exist.', $nodeId));
        }

        return $node;
    }

    /**
     * @param Request $request
     * @param string  $topologyName
     * @param string  $nodeName
     *
     * @throws MongoDBException
     * @throws PipesFrameworkException
     * @throws StartingPointException
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
     *
     * @throws LockException
     * @throws MappingException
     * @throws PipesFrameworkException
     * @throws StartingPointException
     */
    public function runWithRequestById(Request $request, string $topologyId, string $nodeId): void
    {
        $this->startingPoint->runWithRequest($request, $this->getTopology($topologyId), $this->getNode($nodeId));
    }

    /**
     * @param string      $topologyName
     * @param string      $nodeName
     * @param string|null $body
     *
     * @throws MongoDBException
     * @throws PipesFrameworkException
     * @throws StartingPointException
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
     * @throws LockException
     * @throws MappingException
     * @throws PipesFrameworkException
     * @throws CurlException
     * @throws StartingPointException
     */
    public function runTest(string $topologyId): array
    {
        $startTopology = TRUE;
        $runningInfo   = $this->requestHandler->infoTopology($topologyId);
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

        $topology = $this->getTopology($topologyId);
        $res      = $this->startingPoint->runTest($topology);

        if ($topology->getVisibility() === TopologyStatusEnum::DRAFT) {
            $this->requestHandler->deleteTopology($topologyId);
        }

        return $res;
    }

}
