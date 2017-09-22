<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:07 PM
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
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
     * @param string $topologyName
     *
     * @return Topology[]
     * @throws Exception
     */
    public function getTopologies(string $topologyName): array
    {
        $topologies = $this->dm->getRepository(Topology::class)->findBy(['name' => $topologyName, 'enabled' => TRUE]);

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
        $node = $this->dm->getRepository(Node::class)->findOneBy([
            'name'     => $nodeName,
            'topology' => $topologyId,
        ]);

        if (empty($node)) {
            throw new Exception(sprintf('The node[name=%s] does not exist.', $nodeName));
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
        $tops = $this->getTopologies($topologyName);
        foreach ($tops as $top) {
            $this->startingPoint->runWithRequest($request, $top, $this->getNode($nodeName, $top->getId()));
        }
    }

    /**
     * @param string      $topologyName
     * @param string      $nodeName
     * @param string|null $param
     */
    public function run(string $topologyName, string $nodeName, ?string $param = NULL): void
    {
        $tops = $this->getTopologies($topologyName);
        foreach ($tops as $top) {
            $this->startingPoint->run($top, $this->getNode($nodeName, $top->getId()), $param);
        }
    }

    /**
     * @param string $topologyName
     *
     * @return array
     */
    public function runTest(string $topologyName): array
    {
        $res  = [];
        $tops = $this->getTopologies($topologyName);
        foreach ($tops as $top) {
            $res[] = $this->startingPoint->runTest($top);
        }

        return $res;
    }

}