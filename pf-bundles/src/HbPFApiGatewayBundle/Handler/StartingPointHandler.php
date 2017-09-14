<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:07 PM
 */

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Commons\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StartingPointHandler
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler
 */
class StartingPointHandler
{

    /**
     * @var DocumentManager
     */
    private $dm;

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
        $this->startingPoint = $startingPoint;
        $this->dm            = $dm;
    }

    /**
     * @param string $topologyId
     *
     * @return Topology
     * @throws Exception
     */
    public function getTopology(string $topologyId): Topology
    {
        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);

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
        $node = $this->dm->getRepository(Node::class)->find($nodeId);

        if (!$node) {
            throw new Exception(sprintf('The node[id=%s] does not exist.', $nodeId));
        }

        return $node;
    }

    /**
     * @param Request $request
     * @param string  $topologyId
     * @param string  $nodeId
     */
    public function runWithRequest(Request $request, string $topologyId, string $nodeId): void
    {
        $this->startingPoint->runWithRequest($request, $this->getTopology($topologyId), $this->getNode($nodeId));
    }

    /**
     * @param string $topologyId
     * @param string $nodeId
     */
    public function run(string $topologyId, string $nodeId): void
    {
        $this->startingPoint->run($this->getTopology($topologyId), $this->getNode($nodeId));
    }

}