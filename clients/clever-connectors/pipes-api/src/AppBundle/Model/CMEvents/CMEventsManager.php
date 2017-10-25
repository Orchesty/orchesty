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
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
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
            $topologies = $this->getTopologiesForRun($systemInstall, $event);
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
    public function saveEventsForSystemInstall(SystemInstall $systemInstall, array &$data): SystemInstall
    {
        $changed = [];

        if (array_key_exists(SystemInstall::EVENT_CREATE, $data) && $data[SystemInstall::EVENT_CREATE] === TRUE) {
            if ($systemInstall->isEventCreate() === FALSE) {
                $changed[] = SystemInstall::EVENT_CREATE;
            }
            $systemInstall->setEventCreate($data[SystemInstall::EVENT_CREATE]);
            unset($data[SystemInstall::EVENT_CREATE]);
        }

        if (
            array_key_exists(SystemInstall::EVENT_UNSUBSCRIBE, $data) &&
            $data[SystemInstall::EVENT_UNSUBSCRIBE] === TRUE
        ) {
            if ($systemInstall->isEventUnsubscribe() === FALSE) {
                $changed[] = SystemInstall::EVENT_UNSUBSCRIBE;
            }
            $systemInstall->setEventUnsubscribe($data[SystemInstall::EVENT_UNSUBSCRIBE]);
            unset($data[SystemInstall::EVENT_UNSUBSCRIBE]);
        }

        if (
            array_key_exists(SystemInstall::EVENT_HARD_BOUNCE, $data) &&
            $data[SystemInstall::EVENT_HARD_BOUNCE] === TRUE
        ) {
            if ($systemInstall->isEventHardBounce() === FALSE) {
                $changed[] = SystemInstall::EVENT_HARD_BOUNCE;
            }
            $systemInstall->setEventHardBounce($data[SystemInstall::EVENT_HARD_BOUNCE]);
            unset($data[SystemInstall::EVENT_HARD_BOUNCE]);
        }

        if (empty($changed)) {
            return $systemInstall;
        }

        $request = new Request([], [], [], [], [], [], json_encode($changed));
        $request->headers->set(CMHeaders::createKey(CMHeaders::GUID), $systemInstall->getUser());
        $request->headers->set(CMHeaders::createKey(CMHeaders::SYSTEM_KEY), $systemInstall->getSystem());
        $request->headers->set(CMHeaders::createKey(CMHeaders::TOKEN), $systemInstall->getToken());

        $topologies = $this->getTopologiesForSave($systemInstall);
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
     * @param SystemInstall $systemInstall
     * @param string        $event
     *
     * @return array
     * @throws CleverConnectorsException
     */
    private function getTopologiesForRun(SystemInstall $systemInstall, string $event): array
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

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CleverConnectorsException
     */
    private function getTopologiesForSave(SystemInstall $systemInstall): array
    {
        $topologies = $this->topologyRepo->getRunnableTopologies(TopologyNameUtils::getSystemCMEventName($systemInstall));
        $name       = TopologyNameUtils::getCMEventName();
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