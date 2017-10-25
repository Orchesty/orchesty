<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.10.17
 * Time: 10:47
 */

namespace CleverConnectors\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CMEventsManager
 *
 * @package CleverConnectors\AppBundle\Model\CM
 */
class CMEventsManager implements LoggerAwareInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ObjectRepository|SystemInstallRepository
     */
    private $systemRepo;

    /**
     * @var ObjectRepository|TopologyRepository
     */
    private $topologyRepo;

    /**
     * @var ObjectRepository|NodeRepository
     */
    private $nodeRepo;

    /**
     * @var StartingPointHandler
     */
    private $startingPoint;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CMEventHandler constructor.
     *
     * @param DocumentManager      $dm
     * @param StartingPointHandler $startingPoint
     */
    public function __construct(DocumentManager $dm, StartingPointHandler $startingPoint)
    {
        $this->dm            = $dm;
        $this->systemRepo    = $this->dm->getRepository(SystemInstall::class);
        $this->topologyRepo  = $this->dm->getRepository(Topology::class);
        $this->nodeRepo      = $this->dm->getRepository(Node::class);
        $this->startingPoint = $startingPoint;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @param string  $userId
     * @param string  $event
     *
     * @throws CleverConnectorsException
     */
    public function runEvent(Request $request, string $userId, string $event): void
    {
        if (!SystemInstall::isEvent($event)) {
            throw new CleverConnectorsException(
                sprintf('Event type ["%s"] is not valid.', $event),
                CleverConnectorsException::INVALID_ENUM_VALUE
            );
        }

        foreach ($this->systemRepo->getSystemInstallByEvent($event, $userId) as $systemInstall) {
            $topologies = $this->getTopologies($systemInstall, $event);
            foreach ($topologies as $topology) {
                try {
                    $node = $this->nodeRepo->getStartingNode($topology);
                    $this->startingPoint->runWithRequest($request, $topology->getName(), $node->getName());
                } catch (Exception $e) {
                    $this->logger->alert($e->getMessage(), ['exception' => $e]);
                }
            }
        }
    }

    /**
     * -------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     * @param string        $event
     *
     * @return array
     * @throws CleverConnectorsException
     */
    private function getTopologies(SystemInstall $systemInstall, string $event): array
    {
        $topologies = $this->topologyRepo->getRunnableTopologies(
            TopologyNameUtils::getCustomEventName($systemInstall, $event)
        );

        $name = TopologyNameUtils::getEventName($systemInstall, $event);
        if (empty($topologies)) {
            $topologies = $this->topologyRepo->getRunnableTopologies($name);
        }

        /** @var Topology $topology */
        if ($topologies) {
            return $topologies;
        }

        throw new CleverConnectorsException(
            sprintf('Topology ["%s"] not found!', $name),
            CleverConnectorsException::TOPOLOGY_NOT_FOUND
        );
    }

}