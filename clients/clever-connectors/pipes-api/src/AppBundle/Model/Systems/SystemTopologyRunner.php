<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SystemTopologyRunner
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
class SystemTopologyRunner implements LoggerAwareInterface
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
     * @var LoggerInterface
     */
    private $logger;

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
        $this->logger             = new NullLogger();
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string          $topology
     * @param SystemInstall   $systemInstall
     * @param SystemInterface $system
     * @param null|Request    $request
     * @param bool            $service
     *
     * @return array
     * @throws MongoDBException
     */
    public function runTopologies(
        string $topology,
        SystemInstall $systemInstall,
        SystemInterface $system,
        ?Request $request = NULL,
        bool $service = FALSE
    ): array
    {
        $name = $this->getTopologyName($topology, $systemInstall->getSystem(), $systemInstall->getUser(), $service);

        $topologies = $this->topologyRepository->getRunnableTopologies($name);

        $this->logger->debug(
            sprintf('Try to find topologies with name "%s": %s found', $name, count($topologies)),
            ['user' => $systemInstall->getUser()]
        );

        if (empty($topologies)) {
            $name = $system->getCustomTopologyName(
                $this->getCustomTopologyName($topology, $systemInstall->getSystem(), $service)
            );

            $topologies = $this->topologyRepository->getRunnableTopologies($name);

            $this->logger->debug(
                sprintf('Try to find topologies with name "%s": %s found', $name, count($topologies)),
                ['user' => $systemInstall->getUser()]
            );
        }

        foreach ($topologies as $topology) {
            $this->logger->debug(
                sprintf('Try to run "%s" topology with name "%s".', $topology->getId(), $topology->getName()),
                ['user' => $systemInstall->getUser()]
            );

            $node = $this->nodeRepository->getStartingNode($topology);
            if ($request) {
                $this->startingPoint->runWithRequest($request, $topology, $node);
            } else {
                $this->startingPoint->run($topology, $node);
            }
        }

        return $topologies;
    }

    /**
     * @param string $topology
     * @param string $system
     * @param string $user
     * @param bool   $service
     *
     * @return string
     */
    private function getTopologyName(string $topology, string $system, string $user, bool $service): string
    {
        return $service
            ? TopologyNameUtils::getServiceTopologyName($topology, $system, $user)
            : TopologyNameUtils::getTopologyName($topology, $system, $user);
    }

    /**
     * @param string $topology
     * @param string $system
     * @param bool   $service
     *
     * @return string
     */
    private function getCustomTopologyName(string $topology, string $system, bool $service): string
    {
        return $service
            ? TopologyNameUtils::getServiceTopologyName($topology, $system)
            : TopologyNameUtils::getTopologyName($topology, $system);
    }

}