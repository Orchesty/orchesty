<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 15:16
 */

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CMEventHandler
 *
 * @package CleverConnectors\AppBundle\Handler
 */
class CMEventsHandler
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var DocumentRepository|SystemInstallRepository
     */
    private $systemRepo;

    /**
     * @var DocumentRepository|TopologyRepository
     */
    private $topologyRepo;

    /**
     * @var DocumentRepository|NodeRepository
     */
    private $nodeRepo;

    /**
     * @var StartingPointHandler
     */
    private $startingPoint;

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
     * @param Request $request
     * @param string  $userId
     *
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function createEvent(Request $request, string $userId): void
    {
        $this->runEvent($request, $userId, SystemInstall::EVENT_CREATE);
    }

    /**
     * @param Request $request
     * @param string  $userId
     *
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function unsubscribeEvent(Request $request, string $userId): void
    {
        $this->runEvent($request, $userId, SystemInstall::EVENT_UNSUBSCRIBE);
    }

    /**
     * @param Request $request
     * @param string  $userId
     *
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function hardBounceEvent(Request $request, string $userId): void
    {
        $this->runEvent($request, $userId, SystemInstall::EVENT_HARD_BOUNCE);
    }

    //@TODO move to CMEventsManager

    /**
     * @param Request $request
     * @param string  $userId
     * @param string  $event
     *
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    private function runEvent(Request $request, string $userId, string $event): void
    {
        if (!SystemInstall::isEvent($event)) {
            throw new CleverConnectorsException(
                sprintf('Event type ["%s"] is not valid.', $event),
                CleverConnectorsException::INVALID_ENUM_VALUE
            );
        }

        foreach ($this->getSystemInstall($userId, $event) as $systemInstall) {
            $topologies = $this->getTopologies($systemInstall, $event);
            foreach ($topologies as $topology) {
                $node = $this->nodeRepo->getStartingNode($topology);
                $this->startingPoint->runWithRequest($request, $topology->getName(), $node->getName());
            }
        }
    }

    /**
     * @param string $userId
     * @param string $event
     *
     * @return array
     * @throws SystemException
     */
    private function getSystemInstall(string $userId, string $event): array
    {
        $systemInstalls = $this->systemRepo->findBy([$event => TRUE, 'user' => $userId]);

        if (!empty($systemInstalls)) {
            return $systemInstalls;
        }

        throw new SystemException(
            sprintf('User ["%s"] can not run event ["%s"]!', $userId, $event),
            SystemException::SYSTEM_NOT_FOUND
        );
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $event
     *
     * @return array
     * @throws CleverConnectorsException
     */
    private function getTopologies(SystemInstall $systemInstall, string $event): array
    {
        $name       = TopologyNameUtils::getEventName($systemInstall, $event);
        $topologies = $this->topologyRepo->getRunnableTopologies(
            TopologyNameUtils::getCustomEventName($systemInstall, $event)
        );

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