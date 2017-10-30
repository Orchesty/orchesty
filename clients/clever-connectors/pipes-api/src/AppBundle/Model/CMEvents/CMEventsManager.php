<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.10.17
 * Time: 10:47
 */

namespace CleverConnectors\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\InnerRequestUtils;
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
 * @package CleverConnectors\AppBundle\Model\CMEvents
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
     * @var SystemLoader
     */
    private $loader;

    /**
     * CMEventHandler constructor.
     *
     * @param DocumentManager      $dm
     * @param StartingPointHandler $startingPoint
     * @param SystemLoader         $loader
     */
    public function __construct(DocumentManager $dm, StartingPointHandler $startingPoint, SystemLoader $loader)
    {
        $this->dm            = $dm;
        $this->systemRepo    = $this->dm->getRepository(SystemInstall::class);
        $this->topologyRepo  = $this->dm->getRepository(Topology::class);
        $this->nodeRepo      = $this->dm->getRepository(Node::class);
        $this->startingPoint = $startingPoint;
        $this->loader        = $loader;
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
        SystemInstall::checkEvent($event);
        $request->headers->set(CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE), $event);
        switch ($event) {
            case SystemInstall::EVENT_CREATE:
                $const = TopologyNameUtils::CREATE_CONTACT;
                break;
            case SystemInstall::EVENT_UNSUBSCRIBE:
                $const = TopologyNameUtils::UNSUBSCRIBE_CONTACT;
                break;
            case SystemInstall::EVENT_HARD_BOUNCE:
                $const = TopologyNameUtils::HARD_BOUNCE_CONTACT;
                break;
            default:
                $const = TopologyNameUtils::UPDATE_CONTACT;
                break;
        }

        /** @var SystemInstall $systemInstall */
        foreach ($this->systemRepo->getSystemInstallByEvent($event, $userId) as $systemInstall) {
            $system     = $this->loader->getSystem($systemInstall->getSystem());
            $topologies = $this->getTopologiesForRun($system, $systemInstall, $const);
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
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveEventsForSystemInstall(
        SystemInstall $systemInstall,
        array &$data
    ): SystemInstall
    {
        /** @var SystemInterface|CMEventSystemInterface $system */
        $system  = $this->loader->getSystem($systemInstall->getSystem());
        $changed = $this->getChanges($system, $systemInstall, $data);

        if (empty($changed)) {
            return $systemInstall;
        }

        $request    = InnerRequestUtils::getRequest($systemInstall, $changed);
        $topologies = $this->getTopologiesForSave($system, $systemInstall);
        foreach ($topologies as $topology) {
            try {
                $node = $this->nodeRepo->getStartingNode($topology);
                $this->startingPoint->runWithRequest($request, $topology->getName(), $node->getName());
            } catch (Exception $e) {
                $this->logger->alert($e->getMessage(), ['exception' => $e]);
            }
        }

        return $systemInstall;
    }

    /**
     * -------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     * @param string          $const
     *
     * @return array
     * @throws CleverConnectorsException
     */
    private function getTopologiesForRun(SystemInterface $system, SystemInstall $systemInstall, string $const): array
    {
        $topologies = $this->topologyRepo->getRunnableTopologies(
            TopologyNameUtils::getTopologyName($const, $systemInstall->getSystem(), $systemInstall->getUser())
        );

        $name = TopologyNameUtils::getTopologyName($const, $systemInstall->getSystem());
        $name = $system->getCustomTopologyName($name);
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

    /**
     * @param SystemInterface|CMEventSystemInterface $system
     * @param SystemInstall                          $systemInstall
     *
     * @return array
     * @throws CleverConnectorsException
     */
    private function getTopologiesForSave(SystemInterface $system, SystemInstall $systemInstall): array
    {
        $topologies = $this->topologyRepo->getRunnableTopologies(
            TopologyNameUtils::getServiceTopologyName(TopologyNameUtils::ACTIVATE_EVENT,
                $systemInstall->getSystem(),
                $systemInstall->getUser()
            )
        );

        if (empty($topologies)) {
            $topologies = $this->topologyRepo->getRunnableTopologies(
                TopologyNameUtils::getServiceTopologyName(
                    TopologyNameUtils::ACTIVATE_EVENT,
                    $systemInstall->getSystem()
                )
            );
        }

        $name = TopologyNameUtils::getServiceTopologyName(TopologyNameUtils::ACTIVATE_EVENT);
        $name = $system->getCustomTopologyName($name);
        if (empty($topologies)) {
            $topologies = $this->topologyRepo->getRunnableTopologies($name);
        }

        /** @var Topology $topology */
        if (empty($topologies)) {
            throw new CleverConnectorsException(
                sprintf('Topology ["%s"] not found!', $name),
                CleverConnectorsException::TOPOLOGY_NOT_FOUND
            );
        }

        return $topologies;
    }

    /**
     * @param CMEventSystemInterface|SystemInterface $system
     * @param SystemInstall                          $systemInstall
     * @param array                                  $data
     *
     * @return array
     */
    private function getChanges(CMEventSystemInterface $system, SystemInstall $systemInstall, array &$data): array
    {
        $changed = [];

        if (array_key_exists(SystemInstall::EVENT_CREATE, $data)) {
            if ($system->isEventAllowed(SystemInstall::EVENT_CREATE) === TRUE) {
                $systemInstall->setEventCreate($data[SystemInstall::EVENT_CREATE]);
            }

            unset($data[SystemInstall::EVENT_CREATE]);
        }

        $this->processEventData($system, $systemInstall, $data, $changed, SystemInstall::EVENT_UNSUBSCRIBE);
        $this->processEventData($system, $systemInstall, $data, $changed, SystemInstall::EVENT_HARD_BOUNCE);

        return $changed;
    }

    /**
     * @param CMEventSystemInterface|SystemInterface $system
     * @param SystemInstall                          $systemInstall
     * @param array                                  $data
     * @param array                                  $changed
     * @param string                                 $event
     */
    private function processEventData(
        CMEventSystemInterface $system,
        SystemInstall $systemInstall,
        array &$data,
        array &$changed,
        string $event
    ): void
    {
        if (array_key_exists($event, $data)) {
            if ($system->isEventAllowed($event) === TRUE) {
                if ($systemInstall->getEventState($event) === FALSE && $data[$event] === TRUE && $system->isEventProcessAllowed($event)) {
                    $changed[] = $event;
                }

                $systemInstall->setEventState($event, $data[$event]);
            }
            unset($data[$event]);
        }
    }

}