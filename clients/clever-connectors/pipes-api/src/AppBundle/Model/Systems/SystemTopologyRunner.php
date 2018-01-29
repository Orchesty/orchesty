<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SystemTopologyRunner
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
class SystemTopologyRunner
{

    /**
     * @var ObjectRepository|TopologyRepository
     */
    private $topologyRepository;

    /**
     * @var ObjectRepository|NodeRepository
     */
    private $nodeRepository;

    /**
     * @var StartingPoint
     */
    private $startingPoint;

    /**
     * SystemTopologyRunner constructor.
     *
     * @param DocumentManager $dm
     * @param StartingPoint   $startingPoint
     */
    public function __construct(DocumentManager $dm, StartingPoint $startingPoint)
    {
        $this->topologyRepository = $dm->getRepository(Topology::class);
        $this->nodeRepository     = $dm->getRepository(Node::class);
        $this->startingPoint      = $startingPoint;
    }

    /**
     * @param string          $topology
     * @param SystemInstall   $systemInstall
     * @param SystemInterface $system
     * @param Request|null    $request
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function runTopologies(
        string $topology,
        SystemInstall $systemInstall,
        SystemInterface $system,
        ?Request $request = NULL
    ): array
    {
        $name = TopologyNameUtils::getTopologyName(
            $topology,
            $systemInstall->getSystem(),
            $systemInstall->getUser()
        );
        $topologies = $this->topologyRepository->getRunnableTopologies($name);

        if (empty($topologies)) {
            $name       = $system->getCustomTopologyName(
                TopologyNameUtils::getTopologyName($topology, $systemInstall->getSystem())
            );
            $topologies = $this->topologyRepository->getRunnableTopologies($name);
        }

        foreach ($topologies as $topology) {
            $node = $this->nodeRepository->getStartingNode($topology);
            if ($request) {
                $this->startingPoint->runWithRequest($request, $topology, $node);
            } else {
                $this->startingPoint->run($topology, $node);
            }
        }

        return $topologies;
    }

}